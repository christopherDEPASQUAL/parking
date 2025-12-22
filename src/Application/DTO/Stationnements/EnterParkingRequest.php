<?php declare(strict_types=1);

namespace App\Application\DTO\Stationnements;

final class EnterParkingRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $parkingId,
        public readonly ?string $reservationId = null,
        public readonly ?string $abonnementId = null,
        public readonly ?\DateTimeImmutable $at = null
    ) {}

    public static function fromArray(array $data): self
    {
        $at = isset($data['at']) ? new \DateTimeImmutable($data['at']) : null;

        return new self(
            $data['user_id'] ?? throw new \InvalidArgumentException('user_id is required'),
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $data['reservation_id'] ?? null,
            $data['abonnement_id'] ?? null,
            $at
        );
    }
}
