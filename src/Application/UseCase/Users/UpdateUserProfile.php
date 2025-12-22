<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

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
        $userIdVO = UserId::fromString($userId);
        $user = $this->userRepository->findById($userIdVO);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        if ($email !== null) {
            $emailVO = Email::fromString($email);

            if (!$user->getEmail()->equals($emailVO)) {
                if ($this->userRepository->existsByEmail($emailVO)) {
                    throw new \InvalidArgumentException("Email already exists");
                }
                $user->changeEmail($emailVO);
            }
        }

        if ($firstName !== null || $lastName !== null) {
            $user->changeName(
                $firstName ?? $user->getFirstName(),
                $lastName ?? $user->getLastName()
            );
        }

        $this->userRepository->save($user);

        return $user;
    }
}