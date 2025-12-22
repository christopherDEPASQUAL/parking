<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class GetParkingMonthlyRevenueRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly int $year,
        public readonly int $month
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            (int) ($data['year'] ?? throw new \InvalidArgumentException('year is required')),
            (int) ($data['month'] ?? throw new \InvalidArgumentException('month is required'))
        );
    }
}
