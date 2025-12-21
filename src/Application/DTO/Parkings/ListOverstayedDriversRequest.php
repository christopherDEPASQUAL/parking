<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ListOverstayedDriversRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly ?\DateTimeImmutable $at = null
    ) {}

    public static function fromArray(array $data): self
    {
        $at = isset($data['at']) ? new \DateTimeImmutable($data['at']) : null;

        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $at
        );
    }
}
