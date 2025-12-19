<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\PasswordHash;

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
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        $storedPasswordHash = PasswordHash::fromHash($user->getPasswordHash());

        if (!$this->passwordHasher->verify($currentPassword, $storedPasswordHash)) {
            throw new \InvalidArgumentException("Current password is incorrect");
        }

        $newPasswordHash = $this->passwordHasher->hash($newPassword);

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