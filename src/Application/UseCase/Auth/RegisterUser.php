<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;

final class RegisterUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher
    ) {}

    public function execute(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        UserRole $role = UserRole::CLIENT
    ): User {
        $emailVO = Email::fromString($email);

        if ($this->userRepository->existsByEmail($emailVO)) {
            throw new \InvalidArgumentException("Email already exists");
        }

        $passwordHash = $this->passwordHasher->hash($password);

        $user = User::register(
            $emailVO,
            $passwordHash,
            $role,
            $firstName,
            $lastName
        );

        $this->userRepository->save($user);

        return $user;
    }
}