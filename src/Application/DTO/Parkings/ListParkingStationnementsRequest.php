<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ListParkingStationnementsRequest
{
    public function __construct(
        public readonly string $parkingId
    ) {}
}

