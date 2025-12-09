<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\Entity\UserRole;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;

    public function delete(string $id): void;

    public function existsByEmail(string $email): bool;

    public function findAll(): array;

    public function findByRole(UserRole $role): array;
}