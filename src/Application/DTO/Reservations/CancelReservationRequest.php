<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

/**
 * Données d'entrée pour annuler une réservation.
 */
final class CancelReservationRequest
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $actorUserId,
        public readonly ?string $reason = null
    ) {
    }
}
