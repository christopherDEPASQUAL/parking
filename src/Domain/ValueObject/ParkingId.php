<?php

declare(strict_types=1);

namespace Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value Object représentant l'identifiant unique d'un Parking.
 * Entièrement indépendant de toute bibliothèque externe.
 */
class ParkingId
{
    private string $value;

    private function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Le ParkingId ne peut pas être vide.');
        }

        $this->value = $value;
    }

    /**
     * Crée un ParkingId à partir d'une valeur brute.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Génère un identifiant unique de type UUID v4 sans dépendance externe.
     */
    public static function generate(): self
    {
        // Génération d’un UUID v4 "fait maison"
        $data = random_bytes(16);

        // Version (4 bits) et variant (2 bits)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return new self($uuid);
    }

    /**
     * Retourne la valeur brute de l'identifiant.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Vérifie l’égalité entre deux ParkingId.
     */
    public function equals(ParkingId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
