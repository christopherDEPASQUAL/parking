<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ExitParkingResponse
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $durationMinutes,
        public readonly int $basePriceCents,
        public readonly int $penaltyCents,
        public readonly int $totalPriceCents,
        public readonly string $exitedAt
    ) {}
}

