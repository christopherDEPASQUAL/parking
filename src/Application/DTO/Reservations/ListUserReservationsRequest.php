<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

final class ListUserReservationsRequest
{
    public function __construct(
        public readonly string $userId
    ) {}
}

