<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySQL;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordHash;
use App\Domain\ValueObject\UserId;
use PDO;

final class SqlUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $connection) {}

    public function findById(UserId $id): ?User
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => (string) $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => (string) $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function save(User $user): void
    {
        $sql = <<<SQL
            INSERT INTO users (id, email, password_hash, first_name, last_name, role, created_at, updated_at)
            VALUES (:id, :email, :password_hash, :first_name, :last_name, :role, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                password_hash = VALUES(password_hash),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                role = VALUES(role),
                updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'id' => (string) $user->getId(),
            'email' => (string) $user->getEmail(),
            'password_hash' => (string) $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'role' => $user->getRole()->value,
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function existsByEmail(Email $email): bool
    {
        $stmt = $this->connection->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute(['email' => (string) $email]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): User
    {
        return User::fromPersistence(
            UserId::fromString($data['id']),
            Email::fromString($data['email']),
            PasswordHash::fromHash($data['password_hash']),
            UserRole::from($data['role']),
            $data['first_name'],
            $data['last_name'],
            new \DateTimeImmutable($data['created_at']),
            $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null
        );
    }
}

