<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\MySQL\SqlUserRepository;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;

/**
 * SQL adapter that delegates to the MySQL repository.
 */
final class UserRepositorySql implements UserRepositoryInterface
{
    private SqlUserRepository $delegate;

    public function __construct(PdoConnectionFactory $connectionFactory)
    {
        $this->delegate = new SqlUserRepository($connectionFactory->create());
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
