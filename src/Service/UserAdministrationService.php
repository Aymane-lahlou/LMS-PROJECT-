<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserAdministrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
        * @return list<User>
        */
    public function getPendingUsers(): array
    {
        return $this->userRepository->findBy(['status' => User::STATUS_PENDING]);
    }

    public function activateUser(User $user): User
    {
        $user->setStatus(User::STATUS_ACTIVE);
        $this->entityManager->flush();

        return $user;
    }

    public function banUser(User $user): User
    {
        $user->setStatus(User::STATUS_BANNED);
        $this->entityManager->flush();

        return $user;
    }

    public function unbanUser(User $user): User
    {
        $user->setStatus(User::STATUS_ACTIVE);
        $this->entityManager->flush();

        return $user;
    }
}
