<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ExitParkingRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $parkingId,
        public readonly ?string $exitedAt = null
    ) {}
}

