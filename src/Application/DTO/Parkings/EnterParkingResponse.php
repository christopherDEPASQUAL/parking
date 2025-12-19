<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class EnterParkingResponse
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $parkingId,
        public readonly string $spotId,
        public readonly string $enteredAt
    ) {}
}

