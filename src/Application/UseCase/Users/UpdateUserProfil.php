<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

final class UpdateUserProfile
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(
        string $userId,
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null
    ): User {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        if ($email !== null && $email !== $user->getEmail()) {
            if ($this->userRepository->existsByEmail($email)) {
                throw new \InvalidArgumentException("Email already exists");
            }
        }

        $updatedUser = User::fromPersistence(
            $user->getId(),
            $email ?? $user->getEmail(),
            $user->getPasswordHash(),
            $user->getRole(),
            $firstName ?? $user->getFirstName(),
            $lastName ?? $user->getLastName(),
            $user->getCreatedAt(),
            new \DateTimeImmutable()
        );

        $this->userRepository->save($updatedUser);

        return $updatedUser;
    }
}