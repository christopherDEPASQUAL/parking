<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Json;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordHash;
use App\Domain\ValueObject\UserId;

final class JsonUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly string $filePath)
    {
        if (!file_exists($this->filePath)) {
            $directory = dirname($this->filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function findById(UserId $id): ?User
    {
        $records = $this->readAll();

        if (!isset($records[(string) $id])) {
            return null;
        }

        return $this->hydrate($records[(string) $id]);
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->readAll() as $record) {
            if ($record['email'] === (string) $email) {
                return $this->hydrate($record);
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $records = $this->readAll();
        $records[(string) $user->getId()] = [
            'id' => (string) $user->getId(),
            'email' => (string) $user->getEmail(),
            'password_hash' => (string) $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'role' => $user->getRole()->value,
            'created_at' => $user->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ];

        file_put_contents($this->filePath, json_encode($records, JSON_PRETTY_PRINT));
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function delete(UserId $id): void
    {
        $records = $this->readAll();
        $idString = (string) $id;

        if (isset($records[$idString])) {
            unset($records[$idString]);
            file_put_contents($this->filePath, json_encode($records, JSON_PRETTY_PRINT));
        }
    }

    public function findAll(): array
    {
        $records = $this->readAll();
        $users = [];

        foreach ($records as $record) {
            $users[] = $this->hydrate($record);
        }

        return $users;
    }

    public function findByRole(UserRole $role): array
    {
        $records = $this->readAll();
        $users = [];

        foreach ($records as $record) {
            $user = $this->hydrate($record);
            if ($user->getRole() === $role) {
                $users[] = $user;
            }
        }

        return $users;
    }

    private function readAll(): array
    {
        $contents = file_get_contents($this->filePath);
        if ($contents === false || $contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function hydrate(array $record): User
    {
        return User::fromPersistence(
            UserId::fromString($record['id']),
            Email::fromString($record['email']),
            PasswordHash::fromHash($record['password_hash']),
            UserRole::from($record['role']),
            $record['first_name'],
            $record['last_name'],
            new \DateTimeImmutable($record['created_at']),
            isset($record['updated_at']) && $record['updated_at'] !== null
                ? new \DateTimeImmutable($record['updated_at'])
                : null
        );
    }
}