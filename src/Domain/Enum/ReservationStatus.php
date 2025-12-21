<?php declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Reservation status (PENDING, CONFIRMED, CANCELLED, PAYMENT_FAILED).
 */
enum ReservationStatus: string
{
    case PENDING_PAYMENT = 'PENDING_PAYMENT';
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case COMPLETED = 'COMPLETED';

    public function canBeCancelled(): bool
    {
        return $this === self::PENDING_PAYMENT || $this === self::PENDING || $this === self::CONFIRMED;
    }

    public function isActive(): bool
    {
        return $this === self::PENDING_PAYMENT || $this === self::PENDING || $this === self::CONFIRMED;
    }

    public function isEntryAllowed(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }
}
