<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

/**
 * Données d'entrée pour lister les réservations d'un parking (avec filtres simples).
 */
final class ListParkingReservationsRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly ?string $status = null,
        public readonly ?\DateTimeImmutable $from = null,
        public readonly ?\DateTimeImmutable $to = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20
    ) {
    }
}
