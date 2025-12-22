<?php declare(strict_types=1);

namespace App\Application\DTO\Reservations;

final class GetReservationInvoiceRequest
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $userId
    ) {}
}

