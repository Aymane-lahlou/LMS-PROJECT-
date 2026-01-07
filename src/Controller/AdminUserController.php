<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserAdministrationService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tab = $request->query->get('tab', 'requests'); // requests, teachers, students
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('q');
        $limit = 10;

        $qb = match ($tab) {
            'teachers' => $userRepository->getUsersByRoleQueryBuilder('ROLE_TEACHER', $search),
            'students' => $userRepository->getUsersByRoleQueryBuilder('ROLE_STUDENT', $search),
            default => $userRepository->getPendingUsersQueryBuilder($search),
        };

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);

        return $this->render('admin/users/index.html.twig', [
            'users' => $paginator,
            'tab' => $tab,
            'currentPage' => $page,
            'pagesCount' => $pagesCount,
            'totalItems' => $totalItems,
            'search' => $search
        ]);
    }
}
