<?php declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;

/**
 * Domain event emitted when a Reservation is successfully created.
 *
 * Notes:
 *  - Pure data (no side effects).
 *  - Handlers live in Application layer.
 */
final class ReservationCreated
{
    private readonly ReservationId $reservationId;
    private readonly UserId $userId;
    private readonly ParkingId $parkingId;
    private readonly DateRange $dateRange;
    private readonly Money $price;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        ReservationId $reservationId,
        UserId $userId,
        ParkingId $parkingId,
        DateRange $dateRange,
        Money $price,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->reservationId = $reservationId;
        $this->userId = $userId;
        $this->parkingId = $parkingId;
        $this->dateRange = $dateRange;
        $this->price = $price;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
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

    public function dateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
