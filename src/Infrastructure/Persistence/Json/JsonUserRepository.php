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
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

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
        if (!isset($records[$id->getValue()])) {
            return null;
        }

        return $this->hydrate($records[$id->getValue()]);
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->readAll() as $record) {
            if (($record['email'] ?? null) === $email->getValue()) {
                return $this->hydrate($record);
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $records = $this->readAll();
        $records[$user->getId()->getValue()] = [
            'id' => $user->getId()->getValue(),
            'email' => $user->getEmail()->getValue(),
            'password_hash' => (string) $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'role' => $user->getRole()->value,
            'created_at' => $user->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ];

        $this->persist($records);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function delete(UserId $id): void
    {
        $records = $this->readAll();
        unset($records[$id->getValue()]);
        $this->persist($records);
    }

    /**
     * @return User[]
     */
    public function findAll(): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            $result[] = $this->hydrate($record);
        }

        return $result;
    }

    /**
     * @return User[]
     */
    public function findByRole(UserRole $role): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['role'] ?? null) === $role->value) {
                $result[] = $this->hydrate($record);
            }
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readAll(): array
    {
        $contents = file_get_contents($this->filePath);
        if ($contents === false || $contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function hydrate(array $record): User
    {
        return User::fromPersistence(
            UserId::fromString((string) $record['id']),
            Email::fromString((string) $record['email']),
            PasswordHash::fromHash((string) $record['password_hash']),
            UserRole::from((string) $record['role']),
            (string) $record['first_name'],
            (string) $record['last_name'],
            new \DateTimeImmutable((string) $record['created_at']),
            isset($record['updated_at']) && $record['updated_at'] !== null
                ? new \DateTimeImmutable((string) $record['updated_at'])
                : null
        );
    }
}
