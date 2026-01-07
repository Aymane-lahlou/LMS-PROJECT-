<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserStatusChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() !== User::STATUS_ACTIVE) {
            throw new CustomUserMessageAccountStatusException('Your account is not active yet. Please contact an administrator.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No-op; pre-auth covers the active check.
    }
}
