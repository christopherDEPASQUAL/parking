<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * DateRange immutable object (start/end) with basic checks.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class DateRange
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        if ($start >= $end) {
            throw new \InvalidArgumentException('La date de début doit être strictement avant la date de fin.');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function start(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): \DateTimeImmutable
    {
        return $this->end;
    }
}
