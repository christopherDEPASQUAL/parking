<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

/**
 * Données de sortie après création de réservation.
 */
final class CreateReservationResponse
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $status,
        public readonly int $priceCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $startsAt,
        public readonly \DateTimeImmutable $endsAt
    ) {
    }
}
