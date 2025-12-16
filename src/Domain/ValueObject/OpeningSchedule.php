<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Représente les plages d'ouverture hebdomadaires d'un parking.
 *
 * Stocke des intervalles par jour (0 = dimanche ... 6 = samedi) en minutes depuis minuit.
 * Permet de vérifier l'ouverture à un instant donné.
 */
final class OpeningSchedule
{
    private const MINUTES_PER_DAY = 1440;

    /**
     * @var array<int, array<int, array{start:int,end:int}>> key: dayOfWeek => list of intervals
     */
    private array $dailyIntervals;

    /**
     * @param array<int, array<int, array{start:string,end:string}>> $dailyIntervals day => list of intervals HH:MM
     */
    public function __construct(array $dailyIntervals)
    {
        $this->dailyIntervals = $this->normalize($dailyIntervals);
    }

    /**
     * Planning toujours ouvert (24/7).
     */
    public static function alwaysOpen(): self
    {
        return new self([
            0 => [['start' => '00:00', 'end' => '24:00']],
            1 => [['start' => '00:00', 'end' => '24:00']],
            2 => [['start' => '00:00', 'end' => '24:00']],
            3 => [['start' => '00:00', 'end' => '24:00']],
            4 => [['start' => '00:00', 'end' => '24:00']],
            5 => [['start' => '00:00', 'end' => '24:00']],
            6 => [['start' => '00:00', 'end' => '24:00']],
        ]);
    }

    public function isOpenAt(DateTimeImmutable $at): bool
    {
        $day = (int) $at->format('w'); // 0 (dimanche) à 6 (samedi)
        $minutes = ((int) $at->format('H')) * 60 + (int) $at->format('i');

        if (!isset($this->dailyIntervals[$day])) {
            return false;
        }

        foreach ($this->dailyIntervals[$day] as $interval) {
            if ($minutes >= $interval['start'] && $minutes < $interval['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<int, array{start:string,end:string}>>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->dailyIntervals as $day => $intervals) {
            foreach ($intervals as $interval) {
                $result[$day][] = [
                    'start' => $this->minutesToString($interval['start']),
                    'end'   => $this->minutesToString($interval['end']),
                ];
            }
        }

        return $result;
    }

    /**
     * @param array<int, array<int, array{start:string,end:string}>> $input
     * @return array<int, array<int, array{start:int,end:int}>>
     */
    private function normalize(array $input): array
    {
        $normalized = [];

        foreach ($input as $day => $intervals) {
            if (!is_int($day) || $day < 0 || $day > 6) {
                throw new InvalidArgumentException('Day must be an integer between 0 (Sunday) and 6 (Saturday).');
            }
            if (!is_array($intervals) || $intervals === []) {
                continue;
            }

            $normalized[$day] = [];
            foreach ($intervals as $interval) {
                if (!isset($interval['start'], $interval['end'])) {
                    throw new InvalidArgumentException('Each interval must define start and end.');
                }

                $start = $this->timeToMinutes($interval['start']);
                $end = $this->timeToMinutes($interval['end']);

                if ($start >= $end) {
                    throw new InvalidArgumentException('Start time must be before end time.');
                }

                if ($end > self::MINUTES_PER_DAY) {
                    throw new InvalidArgumentException('End time cannot exceed 24:00.');
                }

                $this->assertNoOverlap($normalized[$day], $start, $end);

                $normalized[$day][] = ['start' => $start, 'end' => $end];
            }

            // Tri par heure de début pour cohérence
            usort($normalized[$day], static fn(array $a, array $b) => $a['start'] <=> $b['start']);
        }

        return $normalized;
    }

    /**
     * @param array<int, array{start:int,end:int}> $existing
     */
    private function assertNoOverlap(array $existing, int $start, int $end): void
    {
        foreach ($existing as $interval) {
            $overlap = !($end <= $interval['start'] || $start >= $interval['end']);
            if ($overlap) {
                throw new InvalidArgumentException('Opening intervals must not overlap.');
            }
        }
    }

    private function timeToMinutes(string $time): int
    {
        if (!preg_match('/^(2[0-4]|[01]?\\d):([0-5]\\d)$/', $time, $m)) {
            throw new InvalidArgumentException('Time must be in HH:MM format.');
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];
        return $hours * 60 + $minutes;
    }

    private function minutesToString(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
}
