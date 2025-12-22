<?php declare(strict_types=1);

namespace App\Application\DTO\SubscriptionOffers;

final class CreateSubscriptionOfferRequest
{
    /**
     * @param array<int, array<string, mixed>> $weeklyTimeSlots
     */
    public function __construct(
        public readonly string $parkingId,
        public readonly string $label,
        public readonly string $type,
        public readonly int $priceCents,
        public readonly array $weeklyTimeSlots,
        public readonly ?string $status = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $data['label'] ?? throw new \InvalidArgumentException('label is required'),
            $data['type'] ?? 'custom',
            isset($data['price_cents']) ? (int) $data['price_cents'] : throw new \InvalidArgumentException('price_cents is required'),
            $data['weekly_time_slots'] ?? [],
            $data['status'] ?? null
        );
    }
}
