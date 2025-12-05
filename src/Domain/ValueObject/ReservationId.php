<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value Object immuable pour l'identifiant d'une reservation (UUID v4).
 */
final class ReservationId implements \JsonSerializable
{
    private const UUID_V4_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    private string $value;

    private function __construct(string $value)
    {
        $value = trim(strtolower($value));
        if (!preg_match(self::UUID_V4_REGEX, $value)) {
            throw new InvalidArgumentException('ReservationId must be a valid UUID v4.');
        }

        $this->value = $value;
    }

    /** Cree un ReservationId a partir d\'une valeur brute (deserialisation). */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /** Genere un identifiant unique de type UUID v4 sans dependance externe. */
    public static function generate(): self
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return new self($uuid);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /** @deprecated Utiliser getValue(). */
    public function value(): string
    {
        return $this->value;
    }

    public function equals(ReservationId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return ['reservationId' => $this->value];
    }
}
