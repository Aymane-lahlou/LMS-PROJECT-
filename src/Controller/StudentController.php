<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Resource as LessonResource;
use App\Repository\EnrollmentRepository;
use App\Repository\CourseRepository;
use App\Service\ProgressService;
use App\Service\QuizService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StudentController extends AbstractController
{
    #[Route('/student/dashboard', name: 'student_dashboard')]
    public function dashboard(
        EnrollmentRepository $enrollmentRepository,
        CourseRepository $courseRepository,
        ProgressService $progressService,
        \App\Repository\ResourceProgressRepository $resourceProgressRepository,
        \App\Repository\AttemptRepository $attemptRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $user = $this->getUser();
        $enrolledCourses = [];
        $availableCourses = [];
        $progressByCourse = [];
        
        // Progress Data Containers
        $completedResources = [];
        $passedQuizzes = [];
        $completedLessons = [];

        if ($user?->getSpecialty() && $user?->getStudyYear()) {
            $candidateCourses = $courseRepository->findForStudent($user->getSpecialty(), (int) $user->getStudyYear());
            $enrolledIds = $enrollmentRepository->findCourseIdsForStudent($user->getId());
            
            // Bulk Fetch Progress
            $resourceProgresses = $resourceProgressRepository->findBy(['student' => $user, 'isCompleted' => true]);
            foreach ($resourceProgresses as $rp) {
                if ($rp->getResource()) {
                    $completedResources[$rp->getResource()->getId()] = true;
                }
            }

            $attempts = $attemptRepository->findBy(['student' => $user, 'passed' => true]);
            foreach ($attempts as $at) {
                if ($at->getQuiz()) {
                    $passedQuizzes[$at->getQuiz()->getId()] = true;
                }
            }

            foreach ($candidateCourses as $course) {
                if (in_array($course->getId(), $enrolledIds, true)) {
                    $enrolledCourses[] = $course;
                    $progressByCourse[$course->getId()] = $progressService->getCourseProgressPercentage($course, $user);
                    
                    // Calculate compiled Lesson Status
                    foreach ($course->getLessons() as $lesson) {
                        $isLessonComplete = true;
                        
                        foreach ($lesson->getResources() as $res) {
                            if (!isset($completedResources[$res->getId()])) {
                                $isLessonComplete = false;
                                break;
                            }
                        }
                        
                        if ($isLessonComplete) {
                             foreach ($lesson->getQuizzes() as $quiz) {
                                if (!isset($passedQuizzes[$quiz->getId()])) {
                                    $isLessonComplete = false;
                                    break;
                                }
                            }
                        }

                        if ($isLessonComplete) {
                            $completedLessons[$lesson->getId()] = true;
                        }
                    }

                    continue;
                }

                $availableCourses[] = $course;
            }
        }

        return $this->render('student/dashboard.html.twig', [
            'courses' => $enrolledCourses,
            'availableCourses' => $availableCourses,
            'progressByCourse' => $progressByCourse,
            'completedResources' => $completedResources,
            'passedQuizzes' => $passedQuizzes,
            'completedLessons' => $completedLessons,
        ]);
    }

    #[Route('/student/explore', name: 'student_explore')]
    public function explore(
        Request $request,
        CourseRepository $courseRepository,
        EnrollmentRepository $enrollmentRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        $user = $this->getUser();

        // 1. Get fitler parameters
        $search = $request->query->get('q');
        
        // 2. Fetch courses based on user's specialty/year AND search query
        // If user has no specialty/year set, we might show empty or all (depending on rules). 
        // Requirement says: "Course visibility must still respect the student’s specialty and study year."
        
        $specialty = $user->getSpecialty();
        $year = (int) $user->getStudyYear();

        if (!$specialty || !$year) {
             $this->addFlash('warning', 'Veuillez compléter votre profil (Filière et Année) pour voir les cours adaptés.');
             return $this->redirectToRoute('student_dashboard');
        }

        $allMatchingCourses = $courseRepository->searchCourses($search, $specialty, $year);

        // 3. Separate into "enrolled" and "not enrolled" is not strictly necessary for the explore page 
        // but we want to show "Already Enrolled" status or filter them out.
        // Let's just show all matching courses with an indication.
        
        $enrolledCourseIds = $enrollmentRepository->findCourseIdsForStudent($user->getId());

        return $this->render('student/explore.html.twig', [
            'courses' => $allMatchingCourses,
            'enrolledIds' => $enrolledCourseIds,
            'searchQuery' => $search,
            'userSpecialty' => $specialty,
            'userYear' => $year,
        ]);
    }

    #[Route('/student/resource/{id}', name: 'student_view_resource')]
    public function viewResource(
        LessonResource $resource,
        ProgressService $progressService,
        EnrollmentRepository $enrollmentRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $course = $resource->getLesson()?->getCourse();
        if (!$course || !$enrollmentRepository->isEnrolled($this->getUser()->getId(), $course->getId())) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('student/resource.html.twig', [
            'resourceItem' => $resource,
            'isCompleted' => $progressService->isResourceCompleted($this->getUser(), $resource),
        ]);
    }

    #[Route('/student/resource/{id}/progress', name: 'student_update_progress', methods: ['POST'])]
    public function updateProgress(
        LessonResource $resource,
        Request $request,
        ProgressService $progressService,
        EnrollmentRepository $enrollmentRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        $course = $resource->getLesson()?->getCourse();
        // Strict Access Control
        if (!$course || !$enrollmentRepository->isEnrolled($this->getUser()->getId(), $course->getId())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $timeIncrement = (int) ($data['time'] ?? 0);
        
        if ($timeIncrement > 0) {
            $progressService->addTimeSpent($this->getUser(), $resource, $timeIncrement);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/student/resource/{id}/complete', name: 'student_complete_resource', methods: ['POST'])]
    public function completeResource(
        LessonResource $resource,
        ProgressService $progressService,
        EnrollmentRepository $enrollmentRepository,
        Request $request
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $course = $resource->getLesson()?->getCourse();
        if (!$course || !$enrollmentRepository->isEnrolled($this->getUser()->getId(), $course->getId())) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('complete_resource_'.$resource->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('student_view_resource', ['id' => $resource->getId()]);
        }

        // Only mark as complete, don't reset time
        $progressService->markResourceCompleted($this->getUser(), $resource);
        $this->addFlash('success', 'Ressource marquée comme terminée.');

        return $this->redirectToRoute('student_view_resource', ['id' => $resource->getId()]);
    }

    #[Route('/student/quiz/{id}', name: 'student_take_quiz')]
    public function takeQuiz(
        Request $request,
        Quiz $quiz,
        QuizService $quizService,
        ProgressService $progressService,
        EnrollmentRepository $enrollmentRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $course = $quiz->getLesson()?->getCourse();
        if (!$course || !$enrollmentRepository->isEnrolled($this->getUser()->getId(), $course->getId())) {
            throw $this->createAccessDeniedException();
        }

        $lesson = $quiz->getLesson();
        if (!$progressService->areLessonResourcesCompleted($lesson, $this->getUser())) {
            $this->addFlash('error', 'Vous devez terminer toutes les ressources de la leçon avant de passer le quiz.');
            return $this->redirectToRoute('student_dashboard');
        }

        $definition = $quizService->loadQuizDefinition($quiz);
        $questions = $definition['questions'] ?? [];

        if ($request->isMethod('POST')) {
            $answers = $request->request->all('answers');
            $attempt = $quizService->gradeQuiz($quiz, $this->getUser(), $answers);

            $this->addFlash('success', sprintf('Quiz submitted. Score: %d%% (%s)', $attempt->getScore(), $attempt->isPassed() ? 'passed' : 'failed'));

            return $this->redirectToRoute('student_dashboard');
        }

        return $this->render('student/quiz.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/student/course/{id}/enroll', name: 'student_enroll_course', methods: ['POST'])]
    public function enrollInCourse(
        Request $request,
        \App\Entity\Course $course,
        EnrollmentRepository $enrollmentRepository
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        $student = $this->getUser();

        if (!$student?->getSpecialty() || !$student->getStudyYear()) {
            $this->addFlash('error', 'Complétez votre spécialité et votre année avant de vous inscrire.');
            return $this->redirectToRoute('student_dashboard');
        }

        if ($course->getSpecialty() !== $student->getSpecialty() || (int) $course->getTargetYear() !== (int) $student->getStudyYear()) {
            $this->addFlash('error', 'Vous ne pouvez vous inscrire qu’aux cours de votre spécialité et année.');
            return $this->redirectToRoute('student_dashboard');
        }

        if (!$this->isCsrfTokenValid('enroll_course_'.$course->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('student_dashboard');
        }

        $enrollmentRepository->enroll($student, $course);
        $this->addFlash('success', sprintf('Inscription au cours "%s" réussie.', $course->getTitle()));

        return $this->redirectToRoute('student_dashboard');
    }
}
