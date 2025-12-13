<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\PasswordHash;

final class ChangePassword
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(
        string $userId,
        string $currentPassword,
        string $newPassword
    ): User {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        if (!$user->verifyPassword($currentPassword)) {
            throw new \InvalidArgumentException("Current password is incorrect");
        }

        $newPasswordHash = PasswordHash::fromPlainText($newPassword);

        $updatedUser = User::fromPersistence(
            $user->getId(),
            $user->getEmail(),
            $newPasswordHash->getHash(),
            $user->getRole(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getCreatedAt(),
            new \DateTimeImmutable()
        );

        $this->userRepository->save($updatedUser);

        return $updatedUser;
    }
}