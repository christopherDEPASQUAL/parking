<?php declare(strict_types=1);

namespace App\Application\DTO\Abonnements;

final class CreateAbonnementRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $parkingId,
        public readonly string $offerId,
        public readonly \DateTimeImmutable $startDate,
        public readonly \DateTimeImmutable $endDate,
        public readonly ?string $status = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['user_id'] ?? throw new \InvalidArgumentException('user_id is required'),
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $data['offer_id'] ?? throw new \InvalidArgumentException('offer_id is required'),
            new \DateTimeImmutable($data['start_date'] ?? throw new \InvalidArgumentException('start_date is required')),
            new \DateTimeImmutable($data['end_date'] ?? throw new \InvalidArgumentException('end_date is required')),
            $data['status'] ?? null
        );
    }
}
