<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Opening schedule expressed as weekly slots.
 *
 * Slot format:
 * - start_day (0=Sunday .. 6=Saturday)
 * - end_day (0=Sunday .. 6=Saturday)
 * - start_time / end_time in HH:MM
 */
final class OpeningSchedule
{
    private const MINUTES_PER_DAY = 1440;
    private const MINUTES_PER_WEEK = 10080;

    /**
     * @var array<int, array{start_day:int,end_day:int,start_time:string,end_time:string,start:int,end:int}>
     */
    private array $slots;

    /**
     * @param array<int, array<string, mixed>> $slots
     */
    public function __construct(array $slots)
    {
        $this->slots = $this->normalize($slots);
    }

    public static function alwaysOpen(): self
    {
        $slots = [];
        for ($day = 0; $day <= 6; $day++) {
            $slots[] = [
                'start_day' => $day,
                'end_day' => $day,
                'start_time' => '00:00',
                'end_time' => '24:00',
            ];
        }

        return new self($slots);
    }

    public function isOpenAt(DateTimeImmutable $at): bool
    {
        $day = (int) $at->format('w');
        $minutes = $this->toWeekMinutes($day, $at->format('H:i'));
        $minutesNext = $minutes + self::MINUTES_PER_WEEK;

        foreach ($this->slots as $slot) {
            if (($minutes >= $slot['start'] && $minutes < $slot['end'])
                || ($minutesNext >= $slot['start'] && $minutesNext < $slot['end'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->slots as $slot) {
            $result[] = [
                'start_day' => $slot['start_day'],
                'end_day' => $slot['end_day'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $input
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string,start:int,end:int}>
     */
    private function normalize(array $input): array
    {
        $slots = [];

        if ($this->looksLikeDailyMap($input)) {
            foreach ($input as $day => $intervals) {
                if (!is_int($day) || $day < 0 || $day > 6) {
                    throw new InvalidArgumentException('Day must be an integer between 0 (Sunday) and 6 (Saturday).');
                }
                if (!is_array($intervals) || $intervals === []) {
                    continue;
                }

                foreach ($intervals as $interval) {
                    if (!isset($interval['start'], $interval['end'])) {
                        throw new InvalidArgumentException('Each interval must define start and end.');
                    }

                    $slots[] = [
                        'start_day' => $day,
                        'end_day' => $day,
                        'start_time' => (string) $interval['start'],
                        'end_time' => (string) $interval['end'],
                    ];
                }
            }
        } else {
            foreach ($input as $slot) {
                if (!is_array($slot)) {
                    continue;
                }

                if (isset($slot['start_day'], $slot['end_day'], $slot['start_time'], $slot['end_time'])) {
                    $slots[] = [
                        'start_day' => (int) $slot['start_day'],
                        'end_day' => (int) $slot['end_day'],
                        'start_time' => (string) $slot['start_time'],
                        'end_time' => (string) $slot['end_time'],
                    ];
                    continue;
                }

                if (isset($slot['day'], $slot['start'], $slot['end'])) {
                    $day = (int) $slot['day'];
                    $slots[] = [
                        'start_day' => $day,
                        'end_day' => $day,
                        'start_time' => (string) $slot['start'],
                        'end_time' => (string) $slot['end'],
                    ];
                }
            }
        }

        $normalized = [];
        $ranges = [];

        foreach ($slots as $slot) {
            $startDay = (int) $slot['start_day'];
            $endDay = (int) $slot['end_day'];

            if ($startDay < 0 || $startDay > 6 || $endDay < 0 || $endDay > 6) {
                throw new InvalidArgumentException('Days must be between 0 (Sunday) and 6 (Saturday).');
            }

            $startMinutes = $this->timeToMinutes($slot['start_time']);
            $endMinutes = $this->timeToMinutes($slot['end_time']);

            if ($startMinutes === $endMinutes) {
                throw new InvalidArgumentException('Start time must be different from end time.');
            }

            if ($endMinutes > self::MINUTES_PER_DAY) {
                throw new InvalidArgumentException('End time cannot exceed 24:00.');
            }

            if ($endDay === $startDay && $endMinutes < $startMinutes) {
                $endDay = $startDay === 6 ? 0 : $startDay + 1;
            }

            $startWeek = $startDay * self::MINUTES_PER_DAY + $startMinutes;
            $endWeek = $endDay * self::MINUTES_PER_DAY + $endMinutes;

            if ($endWeek < $startWeek) {
                $endWeek += self::MINUTES_PER_WEEK;
            }

            if ($startWeek === $endWeek) {
                throw new InvalidArgumentException('Opening slot cannot have zero duration.');
            }

            $this->assertNoOverlap($ranges, $startWeek, $endWeek);
            $ranges[] = [$startWeek, $endWeek];

            $normalized[] = [
                'start_day' => $startDay,
                'end_day' => $endDay,
                'start_time' => $this->minutesToString($startMinutes),
                'end_time' => $this->minutesToString($endMinutes),
                'start' => $startWeek,
                'end' => $endWeek,
            ];
        }

        usort($normalized, static fn(array $a, array $b) => $a['start'] <=> $b['start']);

        return $normalized;
    }

    /**
     * @param array<int, array{0:int,1:int}> $ranges
     */
    private function assertNoOverlap(array $ranges, int $start, int $end): void
    {
        $newRanges = $this->expandRange($start, $end);

        foreach ($ranges as $range) {
            foreach ($this->expandRange($range[0], $range[1]) as $existing) {
                foreach ($newRanges as $candidate) {
                    $overlap = !($candidate[1] <= $existing[0] || $candidate[0] >= $existing[1]);
                    if ($overlap) {
                        throw new InvalidArgumentException('Opening slots must not overlap.');
                    }
                }
            }
        }
    }

    /**
     * @return array<int, array{0:int,1:int}>
     */
    private function expandRange(int $start, int $end): array
    {
        if ($end <= self::MINUTES_PER_WEEK) {
            return [[$start, $end]];
        }

        return [
            [$start, self::MINUTES_PER_WEEK],
            [0, $end - self::MINUTES_PER_WEEK],
        ];
    }

    private function looksLikeDailyMap(array $input): bool
    {
        if ($input === []) {
            return false;
        }

        foreach ($input as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
            if ($key < 0 || $key > 6) {
                return false;
            }
            if (!is_array($value)) {
                return false;
            }

            foreach ($value as $interval) {
                if (!is_array($interval) || !isset($interval['start'], $interval['end'])) {
                    return false;
                }
            }
        }

        return true;
    }

    private function toWeekMinutes(int $dayOfWeek, string $time): int
    {
        return ($dayOfWeek * self::MINUTES_PER_DAY) + $this->timeToMinutes($time);
    }

    private function timeToMinutes(string $time): int
    {
        if (!preg_match('/^(2[0-4]|[01]?\\d):([0-5]\\d)$/', $time, $m)) {
            throw new InvalidArgumentException('Time must be in HH:MM format.');
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];

        if ($hours === 24 && $minutes !== 0) {
            throw new InvalidArgumentException('24:00 must be used with 00 minutes.');
        }

        return ($hours * 60) + $minutes;
    }

    private function minutesToString(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
}
