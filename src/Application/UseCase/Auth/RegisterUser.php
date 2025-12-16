<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Domain\Entity\User;
use App\Domain\Entity\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use Ramsey\Uuid\Uuid;

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
        if ($this->userRepository->existsByEmail($email)) {
            throw new \InvalidArgumentException("Email already exists");
        }

        $passwordHash = $this->passwordHasher->hash($password);

        $user = User::fromPersistence(
            Uuid::uuid4()->toString(),
            $email,
            $passwordHash->getHash(),
            $role,
            $firstName,
            $lastName,
            new \DateTimeImmutable()
        );

        $this->userRepository->save($user);

        return $user;
    }
}