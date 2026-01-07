<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\UserRepository;
use App\Repository\ResourceRepository;
use App\Repository\AttemptRepository;
use App\Repository\ResourceProgressRepository;
use App\Service\DomainChoiceProvider;
use App\Service\UserAdministrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        UserAdministrationService $administrationService,
        UserRepository $userRepository,
        CourseRepository $courseRepository,
        EnrollmentRepository $enrollmentRepository,
        ResourceRepository $resourceRepository,
        AttemptRepository $attemptRepository,
        ResourceProgressRepository $resourceProgressRepository,
        DomainChoiceProvider $choiceProvider,
        Request $request,
        ManagerRegistry $registry
    ): Response {
        $keyword = $request->query->get('q');
        $specialty = $request->query->get('specialty');
        $year = $request->query->get('year') ? (int) $request->query->get('year') : null;

        $courses = $courseRepository->searchCourses($keyword, $specialty ?: null, $year);
        $chartData = $courseRepository->countBySpecialty();
        $studentsBySpecialty = $userRepository->countStudentsBySpecialty();
        $resources = $resourceRepository->findAll();
        $teachers = $userRepository->findTeachers();
        // $teachers = $userRepository->findTeachers(); // Moved to AdminUserController
        // $students = $userRepository->findStudents(); // Moved to AdminUserController
        $teachers = $userRepository->findTeachers(); // Kept only for specific counts if needed, but dashboard template will be cleaned. Actually, let's keep fetching them if KPIs need them, but the dashboard action shouldn't pass them to template if not used. 
        // Wait, dashboard template uses `teachers` and `students` variables. I will remove them from the render call below.
        // But `teachers` is used for `teacherCourseCounts`.
        $teachers = $userRepository->findTeachers();
        $teacherCourseCounts = [];
        foreach ($teachers as $teacher) {
            $teacherCourseCounts[$teacher->getId()] = $courseRepository->countForTeacher($teacher);
        }

        $courseIds = array_map(static fn ($c) => $c->getId(), $courses);
        $enrollmentCounts = $enrollmentRepository->countByCourseIds($courseIds);

        $topSpecialty = null;
        if (!empty($chartData)) {
            usort($chartData, static fn ($a, $b) => (int) $b['total'] <=> (int) $a['total']);
            $topSpecialty = $chartData[0];
        }

        $kpis = [
            'users' => $userRepository->count([]),
            'admins' => $userRepository->countByRole('ROLE_ADMIN'),
            'teachers' => $userRepository->countByRole('ROLE_TEACHER'),
            'students' => $userRepository->countByRole('ROLE_STUDENT'),
            'courses' => $courseRepository->count([]),
            'resources' => $resourceRepository->count([]),
            'quizzes' => $registry->getRepository(\App\Entity\Quiz::class)->count([]),
            'attempts' => $attemptRepository->countAll(),
        ];

        $resourceStats = [
            'totalProgress' => $resourceProgressRepository->countAll(),
            'completedProgress' => $resourceProgressRepository->countCompleted(),
            'averageTime' => $resourceProgressRepository->averageTimeSpent(),
        ];

        return $this->render('admin/dashboard.html.twig', [
            // 'pendingUsers' => $administrationService->getPendingUsers(), // Removed
            // 'teachers' => $teachers, // Removed
            // 'students' => $students, // Removed
            // 'teacherCourseCounts' => $teacherCourseCounts, // Removed? No, maybe keep logic but don't pass if not displaying. I'll just remove the keys.
            'courses' => $courses,
            'courseEnrollmentCounts' => $enrollmentCounts,
            'specialties' => $choiceProvider->getSpecialtyChoices(),
            'studyYears' => $choiceProvider->getStudyYearChoices(),
            'filters' => [
                'q' => $keyword,
                'specialty' => $specialty,
                'year' => $year,
            ],
            'chartCoursesBySpecialty' => $chartData,
            'topSpecialty' => $topSpecialty,
            'studentsBySpecialty' => $studentsBySpecialty,
            'studentsByYear' => $userRepository->countStudentsByYear(),
            'resources' => $resources,
            'kpis' => $kpis,
            'resourceStats' => $resourceStats,
        ]);
    }

    #[Route('/admin/resource/{id}/download', name: 'admin_download_resource', methods: ['GET'])]
    public function downloadResource(\App\Entity\Resource $resource): Response
    {
        $filePath = $this->getParameter('kernel.project_dir').'/public/'.$resource->getFilePath();
        if (!is_file($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable');
        }

        return $this->file($filePath, basename($filePath), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/admin/users/{id}/activate', name: 'admin_activate_user', methods: ['POST'])]
    public function activateUser(
        Request $request,
        User $user,
        UserAdministrationService $administrationService
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('activate_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $administrationService->activateUser($user);
            $this->addFlash('success', sprintf('Activated %s', $user->getEmail()));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_users', ['tab' => 'requests']);
    }

    #[Route('/admin/users/{id}/ban', name: 'admin_ban_user', methods: ['POST'])]
    public function banUser(
        Request $request,
        User $user,
        UserAdministrationService $administrationService
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('ban_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $administrationService->banUser($user);
            $this->addFlash('success', sprintf('%s banned.', $user->getEmail()));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_users', ['tab' => 'students']); // Defaulting to students or generic users page
    }

    #[Route('/admin/users/{id}/unban', name: 'admin_unban_user', methods: ['POST'])]
    public function unbanUser(
        Request $request,
        User $user,
        UserAdministrationService $administrationService
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('unban_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $administrationService->unbanUser($user);
            $this->addFlash('success', sprintf('%s unbanned.', $user->getEmail()));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_users', ['tab' => 'students']);
    }
    // pendingUsers action removed

    #[Route('/admin/teacher/new', name: 'admin_create_teacher', methods: ['GET', 'POST'])]
    public function createTeacher(
        Request $request,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $userPasswordHasher,
        UserAdministrationService $administrationService,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(\App\Form\TeacherCreationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(['ROLE_TEACHER']);
            $user->setStatus(User::STATUS_ACTIVE);
            
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte enseignant créé avec succès.');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/create_teacher.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
