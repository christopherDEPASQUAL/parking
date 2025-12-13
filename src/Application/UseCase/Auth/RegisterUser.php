<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Domain\Entity\User;
use App\Domain\Entity\UserRole;
use App\Domain\Repository\UserRepositoryInterface;

final class RegisterUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        UserRole $role = UserRole::CLIENT
    ): User {
        if ($this->userRepository->existsByEmail($email)) {
            throw new \InvalidArgumentException("Email already exists");
        }

        $user = User::create(
            $email,
            $password,
            $role,
            $firstName,
            $lastName
        );

        $this->userRepository->save($user);

        return $user;
    }
}