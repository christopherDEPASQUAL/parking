<?php declare(strict_types=1);

namespace App\Application\DTO\Abonnements;

final class ListParkingAbonnementsRequest
{
    public function __construct(
        public readonly string $parkingId
    ) {}
}

