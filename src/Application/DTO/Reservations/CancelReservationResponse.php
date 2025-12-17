<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

/**
 * Données de sortie après annulation d'une réservation.
 */
final class CancelReservationResponse
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $status,
        public readonly ?\DateTimeImmutable $cancelledAt = null,
        public readonly ?string $reason = null
    ) {
    }
}
