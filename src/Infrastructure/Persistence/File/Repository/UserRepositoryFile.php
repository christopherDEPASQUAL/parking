<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Json\JsonUserRepository;

/**
 * File-backed repository wrapper for the JSON driver.
 */
final class UserRepositoryFile implements UserRepositoryInterface
{
    private JsonUserRepository $delegate;

    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('JSON_USER_STORAGE') ?: 'storage/users.json');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 5) . '/' . ltrim($resolved, '/\\');
        }
        $this->delegate = new JsonUserRepository($resolved);
    }

    public function findById(UserId $id): ?User
    {
        return $this->delegate->findById($id);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->delegate->findByEmail($email);
    }

    public function save(User $user): void
    {
        $this->delegate->save($user);
    }

    public function delete(UserId $id): void
    {
        $this->delegate->delete($id);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->delegate->existsByEmail($email);
    }

    public function findAll(): array
    {
        return $this->delegate->findAll();
    }

    public function findByRole(UserRole $role): array
    {
        return $this->delegate->findByRole($role);
    }
}
