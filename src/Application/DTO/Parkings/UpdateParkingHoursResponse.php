<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class UpdateParkingHoursResponse
{
    public function __construct(
        public readonly string $parkingId,
        public readonly array $openingSchedule
    ) {}
}

