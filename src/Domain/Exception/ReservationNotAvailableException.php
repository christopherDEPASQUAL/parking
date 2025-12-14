<?php declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\DateRange;
use DateTimeInterface;

final class ReservationNotAvailableException extends DomainException
{
    public static function forPeriod(DateRange $range, string $parkingId): self
    {
        return new self(
            sprintf(
                'Reservation unavailable for parking "%s" between %s and %s.',
                $parkingId,
                $range->getStart()->format(DateTimeInterface::ATOM),
                $range->getEnd()->format(DateTimeInterface::ATOM)
            ),
            [
                'parking_id' => $parkingId,
                'start' => $range->getStart()->format(DATE_ATOM),
                'end' => $range->getEnd()->format(DATE_ATOM),
            ]
        );
    }
}
