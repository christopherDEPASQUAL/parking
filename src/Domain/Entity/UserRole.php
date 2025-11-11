<?php declare(strict_types=1);

namespace App\Domain\Entity;

final class UserRole
{
    private const ALLOWED_ROLES = ['ADMIN', 'CLIENT', 'PROPRIETOR'];
    private string $role;

    private function __construct(string $role)
    {
        $this->role = $role;
    }

    public static function fromString(string $role): self
    {
        $upperRole = strtoupper(trim($role));

        if (!in_array($upperRole, self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid role "%s". Allowed roles: %s', $role, implode(', ', self::ALLOWED_ROLES))
            );
        }

        return new self($upperRole);
    }

    public function toString(): string
    {
        return $this->role;
    }

    public static function client(): self
    {
        return new self('CLIENT');
    }

    public static function proprietor(): self
    {
        return new self('PROPRIETOR');
    }

    public static function admin(): self
    {
        return new self('ADMIN');
    }

    public function isClient(): bool
    {
        return $this->role === 'CLIENT';
    }

    public function isProprietor(): bool
    {
        return $this->role === 'PROPRIETOR';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function equals(UserRole $other): bool
    {
        return $this->role === $other->role;
    }
}
