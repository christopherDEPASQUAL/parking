<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * PasswordHash value object that encapsulates hashed secret (never plain).
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - Ne hash jamais lui-même : le domaine reçoit un hash déjà calculé (service d'auth/app).
 */
final class PasswordHash
{
    private string $hash;

    private function __construct(string $hash)
    {
        $hash = trim($hash);
        if ($hash === '') {
            throw new \InvalidArgumentException('Password hash cannot be empty.');
        }

        $this->hash = $hash;
    }

    /**
     * Rehydrates a PasswordHash from a stored hash (e.g., database).
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function equals(PasswordHash $other): bool
    {
        return hash_equals($this->hash, $other->hash);
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
