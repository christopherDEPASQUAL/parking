<?php declare(strict_types=1);

namespace App\Application\DTO\Stationnements;

final class ListParkingStationnementsRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly ?\DateTimeImmutable $from = null,
        public readonly ?\DateTimeImmutable $to = null
    ) {}

    public static function fromArray(array $data): self
    {
        $from = isset($data['from']) ? new \DateTimeImmutable($data['from']) : null;
        $to = isset($data['to']) ? new \DateTimeImmutable($data['to']) : null;

        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $from,
            $to
        );
    }
}
