<?php declare(strict_types=1);

namespace App\Application\DTO\Abonnements;

final class SubscribeToAbonnementRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $parkingId,
        public readonly array $weeklyTimeSlots,
        public readonly string $startDate,
        public readonly string $endDate
    ) {}
}

