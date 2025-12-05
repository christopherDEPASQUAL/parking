<?php declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Domain Entity: User
 *
 * Purpose:
 *  - Represents an application user (client/proprietor).
 *  - Holds invariant-protected properties (id, email, role, password hash VO).
 *
 * Notes:
 *  - No persistence/HTTP logic.
 *  - Emits no side effects other than recording domain events if needed.
 */
final class User
{
    private ?int $id;
    private string $email;
    private string $passwordHash;
    private UserRole $role;
    private string $firstName;
    private string $lastName;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        ?int                $id,
        string              $email,
        string              $passwordHash,
        UserRole            $role,
        string              $firstName,
        string              $lastName,
        \DateTimeImmutable  $createdAt,
        ?\DateTimeImmutable $updatedAt = null
    )
    {
        $this->id = $id;
        $this->setEmail($email);
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Register
     */
    public static function create(
        string   $email,
        string   $plainPassword,
        UserRole $role,
        string   $firstName,
        string   $lastName
    ): self
    {
        return new self(
            null,
            $email,
            self::hashPassword($plainPassword),
            $role,
            $firstName,
            $lastName,
            new \DateTimeImmutable()
        );
    }

    /**
     * Repositories
     */
    public static function fromPersistence(
        int                 $id,
        string              $email,
        string              $passwordHash,
        UserRole            $role,
        string              $firstName,
        string              $lastName,
        \DateTimeImmutable  $createdAt,
        ?\DateTimeImmutable $updatedAt = null
    ): self
    {
        return new self($id, $email, $passwordHash, $role, $firstName, $lastName, $createdAt, $updatedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    private function setEmail(string $email): void
    {
        $email = trim($email);

        if (empty($email)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        $this->email = strtolower($email);
    }

    private function setFirstName(string $firstName): void
    {
        $firstName = trim($firstName);

        if (empty($firstName)) {
            throw new \InvalidArgumentException('First name cannot be empty');
        }

        $this->firstName = $firstName;
    }

    private function setLastName(string $lastName): void
    {
        $lastName = trim($lastName);

        if (empty($lastName)) {
            throw new \InvalidArgumentException('Last name cannot be empty');
        }

        $this->lastName = $lastName;
    }

    private static function hashPassword(string $plainPassword): string
    {
        if (strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters');
        }

        return password_hash($plainPassword, PASSWORD_BCRYPT);
    }
}