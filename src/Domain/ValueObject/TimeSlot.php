<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeInterface;

/**
 * Représente un créneau horaire récurrent (sans date).
 */
final class TimeSlot
{
    private int $startMinutes;
    private int $endMinutes;

    private function __construct(int $startMinutes, int $endMinutes)
    {
        if ($startMinutes < 0 || $startMinutes > 1439) {
            throw new \InvalidArgumentException('Start time must be between 00:00 and 23:59.');
        }
        if ($endMinutes < 0 || $endMinutes > 1440) {
            throw new \InvalidArgumentException('End time must be between 00:00 and 24:00.');
        }
        if ($startMinutes >= $endMinutes) {
            throw new \InvalidArgumentException('Start time must be strictly before end time.');
        }

        $this->startMinutes = $startMinutes;
        $this->endMinutes = $endMinutes;
    }

    public static function fromStrings(string $start, string $end): self
    {
        return new self(
            self::parseToMinutes($start),
            self::parseToMinutes($end)
        );
    }

    public static function fromMinutes(int $startMinutes, int $endMinutes): self
    {
        return new self($startMinutes, $endMinutes);
    }

    public function overlaps(TimeSlot $other): bool
    {
        return $this->startMinutes < $other->endMinutes
            && $other->startMinutes < $this->endMinutes;
    }

    public function isWithin(DateTimeInterface $dateTime): bool
    {
        $minutes = ((int) $dateTime->format('H')) * 60 + (int) $dateTime->format('i');

        return $minutes >= $this->startMinutes && $minutes < $this->endMinutes;
    }

    public function getStartMinutes(): int
    {
        return $this->startMinutes;
    }

    public function getEndMinutes(): int
    {
        return $this->endMinutes;
    }

    public function __toString(): string
    {
        return sprintf('%s-%s', $this->formatMinutes($this->startMinutes), $this->formatMinutes($this->endMinutes));
    }

    private static function parseToMinutes(string $time): int
    {
        $date = \DateTimeImmutable::createFromFormat('H:i', $time);

        if ($date === false) {
            throw new \InvalidArgumentException('Time must follow HH:MM format.');
        }

        return ((int) $date->format('H')) * 60 + (int) $date->format('i');
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}

