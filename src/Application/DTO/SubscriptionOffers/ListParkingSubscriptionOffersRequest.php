<?php declare(strict_types=1);

namespace App\Application\DTO\SubscriptionOffers;

final class ListParkingSubscriptionOffersRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly ?string $status = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $data['status'] ?? null
        );
    }
}
