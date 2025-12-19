<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class UpdateParkingHoursRequest
{
    /**
     * @param array<int, array{day: int, start: string, end: string}> $openingHours
     */
    public function __construct(
        public readonly string $parkingId,
        public readonly string $ownerId,
        public readonly array $openingHours
    ) {}
}

