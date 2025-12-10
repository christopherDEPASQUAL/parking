<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordHash;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Domain Entity: User
 *
 * Responsabilités :
 *  - Encapsuler les invariants métier du compte utilisateur (id, email, rôle, hash).
 *  - Offrir des méthodes explicites pour les modifications avec mise à jour du timestamp.
 */
final class User
{
    private UserId $id;
    private Email $email;
    private PasswordHash $passwordHash;
    private UserRole $role;
    private string $firstName;
    private string $lastName;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        UserId             $id,
        Email              $email,
        PasswordHash       $passwordHash,
        UserRole           $role,
        string             $firstName,
        string             $lastName,
        DateTimeImmutable  $createdAt,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->firstName = $this->sanitizeName($firstName, 'First name');
        $this->lastName = $this->sanitizeName($lastName, 'Last name');
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Factory pour un nouvel utilisateur (génère un nouvel identifiant).
     */
    public static function register(
        Email        $email,
        PasswordHash $passwordHash,
        UserRole     $role,
        string       $firstName,
        string       $lastName
    ): self {
        return new self(
            UserId::generate(),
            $email,
            $passwordHash,
            $role,
            $firstName,
            $lastName,
            new DateTimeImmutable()
        );
    }

    /**
     * Reconstitution depuis la persistence.
     */
    public static function fromPersistence(
        UserId             $id,
        Email              $email,
        PasswordHash       $passwordHash,
        UserRole           $role,
        string             $firstName,
        string             $lastName,
        DateTimeImmutable  $createdAt,
        ?DateTimeImmutable $updatedAt = null
    ): self {
        return new self($id, $email, $passwordHash, $role, $firstName, $lastName, $createdAt, $updatedAt);
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return $this->passwordHash->verify($plainPassword);
    }

    public function changeEmail(Email $email): void
    {
        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->touch();
    }

    public function changeName(string $firstName, string $lastName): void
    {
        $newFirst = $this->sanitizeName($firstName, 'First name');
        $newLast = $this->sanitizeName($lastName, 'Last name');

        if ($newFirst === $this->firstName && $newLast === $this->lastName) {
            return;
        }

        $this->firstName = $newFirst;
        $this->lastName = $newLast;
        $this->touch();
    }

    public function changePassword(PasswordHash $newHash): void
    {
        if ($this->passwordHash->equals($newHash)) {
            return;
        }

        $this->passwordHash = $newHash;
        $this->touch();
    }

    public function changeRole(UserRole $role): void
    {
        if ($this->role === $role) {
            return;
        }

        $this->role = $role;
        $this->touch();
    }

    private function sanitizeName(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException($label . ' cannot be empty');
        }

        return $value;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
