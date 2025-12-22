<?php

namespace App\Domain\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class SubscriptionOfferId
{
    private string $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('SubscriptionOfferId cannot be empty');
        }

        if (!self::isValidUuid($value)) {
            throw new InvalidArgumentException('Invalid SubscriptionOfferId format: must be a valid UUID');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(SubscriptionOfferId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    private static function isValidUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        ) === 1;
    }
}
