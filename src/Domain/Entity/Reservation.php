<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\ReservationStatus;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;

/**
 * Domain Entity: Reservation
 *
 * Purpose:
 *  - Represents a booking for a parking slot between a date range.
 *  - Guards state transitions via ReservationStatus enum.
 *  - May record ReservationCreated/Cancelled events.
 */
final class Reservation
{
    private ReservationId $id;
    private UserId $userId;
    private ParkingId $parkingId;
    private DateRange $dateRange;
    private Money $price;
    private ReservationStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $cancelledAt = null;
    private ?string $cancellationReason = null;

    public function __construct(
        ReservationId $id,
        UserId $userId,
        ParkingId $parkingId,
        DateRange $dateRange,
        Money $price,
        ReservationStatus $status = ReservationStatus::PENDING,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->parkingId = $parkingId;
        $this->dateRange = $dateRange;
        $this->price = $price;
        $this->status = $status;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function id(): ReservationId
    {
        return $this->id;
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

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function cancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function confirm(): void
    {
        if ($this->status !== ReservationStatus::PENDING && $this->status !== ReservationStatus::PENDING_PAYMENT) {
            throw new \DomainException('Seules les réservations en attente peuvent être confirmées.');
        }

        $this->status = ReservationStatus::CONFIRMED;
    }

    public function cancel(?string $reason = null): void
    {
        if (!$this->status->canBeCancelled()) {
            throw new \DomainException('Cette réservation ne peut plus être annulée.');
        }

        $this->status = ReservationStatus::CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        $this->cancellationReason = $reason;
    }

    public function markAsCompleted(): void
    {
        if (!$this->status->isActive()) {
            throw new \DomainException('Seules les réservations actives peuvent être terminées.');
        }

        $this->status = ReservationStatus::COMPLETED;
    }

    public function markPaymentFailed(): void
    {
        if ($this->status === ReservationStatus::COMPLETED) {
            throw new \DomainException('Impossible de marquer une réservation terminée comme paiement échoué.');
        }

        $this->status = ReservationStatus::PAYMENT_FAILED;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isActiveAt(\DateTimeImmutable $at): bool
    {
        if (!$this->status->isActive()) {
            return false;
        }

        return $this->dateRange->contains($at);
    }

    public function isEntryAllowedAt(\DateTimeImmutable $at): bool
    {
        if (!$this->status->isEntryAllowed()) {
            return false;
        }

        return $this->dateRange->contains($at);
    }
}
