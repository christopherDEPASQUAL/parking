<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function save(User $user): void;

    public function delete(UserId $id): void;

    public function existsByEmail(Email $email): bool;

    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @return User[]
     */
    public function findByRole(UserRole $role): array;
}
