<?php declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\ParkingId;

/**
 * Domain event emitted when a Reservation is cancelled.
 *
 * Notes:
 *  - Pure data (no side effects).
 *  - Handlers live in Application layer.
 */
final class ReservationCancelled
{
    private readonly ReservationId $reservationId;
    private readonly UserId $userId;
    private readonly ParkingId $parkingId;
    private readonly \DateTimeImmutable $cancelledAt;
    private readonly ?string $reason;

    public function __construct(
        ReservationId $reservationId,
        UserId $userId,
        ParkingId $parkingId,
        ?\DateTimeImmutable $cancelledAt = null,
        ?string $reason = null
    ) {
        $this->reservationId = $reservationId;
        $this->userId = $userId;
        $this->parkingId = $parkingId;
        $this->cancelledAt = $cancelledAt ?? new \DateTimeImmutable();
        $this->reason = $reason;
    }

    public function reservationId(): ReservationId
    {
        return $this->reservationId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function parkingId(): ParkingId
    {
        return $this->parkingId;
    }

    public function cancelledAt(): \DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
