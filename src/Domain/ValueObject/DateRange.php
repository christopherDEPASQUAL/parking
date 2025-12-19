<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * DateRange immutable object (start/end) with overlap checks.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class DateRange
{
    private DateTimeImmutable $start;
    private DateTimeImmutable $end;

    private function __construct(DateTimeImmutable $start, DateTimeImmutable $end)
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('Start date must be before or equal to end date.');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public static function fromDateTimes(DateTimeInterface $start, DateTimeInterface $end): self
    {
        return new self(self::toImmutable($start), self::toImmutable($end));
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Alias pour compatibilité avec le code existant.
     */
    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Alias pour compatibilité avec le code existant.
     */
    public function end(): DateTimeImmutable
    {
        return $this->end;
    }

    public function overlaps(DateRange $other): bool
    {
        return $this->start <= $other->end && $other->start <= $this->end;
    }

    public function contains(DateTimeInterface $moment): bool
    {
        $time = self::toImmutable($moment);

        return $time >= $this->start && $time <= $this->end;
    }

    public function durationInSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    private static function toImmutable(DateTimeInterface $value): DateTimeImmutable
    {
        return $value instanceof DateTimeImmutable
            ? $value
            : DateTimeImmutable::createFromMutable($value);
    }
}
