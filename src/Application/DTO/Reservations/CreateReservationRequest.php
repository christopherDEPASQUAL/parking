<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

/**
 * Données d'entrée pour créer une réservation.
 */
final class CreateReservationRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly string $userId,
        public readonly \DateTimeImmutable $startsAt,
        public readonly \DateTimeImmutable $endsAt
    ) {
    }
}
