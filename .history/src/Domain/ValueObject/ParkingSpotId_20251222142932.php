<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class ParkingSpotId
{
    private string $value;

    private function __construct(string $value)
    {
        if (!self::isValidUuid($value)) {
            throw new InvalidArgumentException('Invalid ParkingSpotId format');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private static function isValidUuid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            $uuid
        ) === 1;
    }
}
