<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

/**
 * Données de sortie pour la liste des réservations d'un parking.
 */
final class ListParkingReservationsResponse
{
    /**
     * @param array<int, array{reservationId:string,userId:string,status:string,startsAt:\DateTimeImmutable,endsAt:\DateTimeImmutable,priceCents:int,currency:string}> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total
    ) {
    }
}
