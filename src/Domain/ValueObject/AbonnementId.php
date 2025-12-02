<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Strongly-typed identifier for Abonnement aggregate.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class AbonnementId
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('AbonnementId cannot be empty.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(AbonnementId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

