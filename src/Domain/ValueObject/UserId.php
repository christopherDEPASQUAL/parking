<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Strongly-typed identifier for User aggregate.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class UserId
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('UserId cannot be empty.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
