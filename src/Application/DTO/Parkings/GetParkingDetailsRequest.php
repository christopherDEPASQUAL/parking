<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class GetParkingDetailsRequest
{
    public function __construct(
        public readonly string $parkingId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required')
        );
    }
}
