<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function registerStudent(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName,
        string $specialty,
        int $studyYear
    ): User {
        $user = new User();
        $user
            ->setEmail($email)
            ->setRoles(['ROLE_STUDENT'])
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setSpecialty($specialty)
            ->setStudyYear((string) $studyYear)
            ->setStatus(User::STATUS_PENDING);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
