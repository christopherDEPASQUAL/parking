<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;

final class ChangePassword
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher
    ) {}

    public function execute(
        string $userId,
        string $currentPassword,
        string $newPassword
    ): User {
        $userIdVO = UserId::fromString($userId);
        $user = $this->userRepository->findById($userIdVO);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        if (!$this->passwordHasher->verify($currentPassword, $user->getPasswordHash())) {
            throw new \InvalidArgumentException("Current password is incorrect");
        }

        $newPasswordHash = $this->passwordHasher->hash($newPassword);
        $user->changePassword($newPasswordHash);

        $this->userRepository->save($user);

        return $user;
    }
}