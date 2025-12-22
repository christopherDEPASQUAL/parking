<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class GetMonthlyRevenueRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly int $year,
        public readonly int $month
    ) {}
}

