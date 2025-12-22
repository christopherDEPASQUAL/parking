<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class EnterParkingRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $parkingId,
        public readonly string $spotId,
        public readonly ?string $enteredAt = null
    ) {}
}

