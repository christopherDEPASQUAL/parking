<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Mapper;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordHash;
use App\Domain\ValueObject\UserId;

final class UserMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId()->getValue(),
            'email' => $user->getEmail()->getValue(),
            'password_hash' => (string) $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'role' => $user->getRole()->value,
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fromArray(array $row): User
    {
        return User::fromPersistence(
            UserId::fromString((string) $row['id']),
            Email::fromString((string) $row['email']),
            PasswordHash::fromHash((string) $row['password_hash']),
            UserRole::from((string) $row['role']),
            (string) $row['first_name'],
            (string) $row['last_name'],
            new \DateTimeImmutable((string) $row['created_at']),
            isset($row['updated_at']) && $row['updated_at'] !== null
                ? new \DateTimeImmutable((string) $row['updated_at'])
                : null
        );
    }
}
