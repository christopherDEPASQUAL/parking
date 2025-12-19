<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class GetMonthlyRevenueResponse
{
    public function __construct(
        public readonly string $parkingId,
        public readonly int $year,
        public readonly int $month,
        public readonly int $revenueCents,
        public readonly string $currency = 'EUR'
    ) {}
}

