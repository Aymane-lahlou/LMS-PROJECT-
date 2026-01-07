<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\StudentRegistrationType;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        CourseRepository $courseRepository,
        UserRepository $userRepository
    ): Response
    {
        $registrationForm = $this->createForm(StudentRegistrationType::class, new User(), [
            'action' => $this->generateUrl('app_register'),
            'method' => 'POST',
        ]);

        $totalCourses = $courseRepository->count([]);
        $totalTeachers = (int) $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :roleTeacher')
            ->setParameter('roleTeacher', '%ROLE_TEACHER%')
            ->getQuery()
            ->getSingleScalarResult();
        $totalStudents = (int) $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :roleStudent')
            ->setParameter('roleStudent', '%ROLE_STUDENT%')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('home/index.html.twig', [
            'registrationForm' => $registrationForm->createView(),
            'stats' => [
                'courses' => $totalCourses,
                'teachers' => $totalTeachers,
                'students' => $totalStudents,
            ],
        ]);
    }
}
