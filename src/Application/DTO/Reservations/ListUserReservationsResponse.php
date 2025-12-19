<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

final class ListUserReservationsResponse
{
    /**
     * @param array<int, array{id: string, parkingId: string, status: string, startsAt: string, endsAt: string, priceCents: int, currency: string}> $reservations
     */
    public function __construct(
        public readonly array $reservations
    ) {}
}

