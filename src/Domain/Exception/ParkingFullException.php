<?php declare(strict_types=1);

namespace App\Domain\Exception;

final class ParkingFullException extends DomainException
{
    public static function forParking(string $parkingId): self
    {
        return new self(
            sprintf('Parking "%s" has reached its maximum capacity.', $parkingId),
            ['parking_id' => $parkingId]
        );
    }
}
