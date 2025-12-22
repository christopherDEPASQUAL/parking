<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\DateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testFromDateTimesAndContains(): void
    {
        $start = new \DateTimeImmutable('2025-01-01 10:00:00');
        $end = new \DateTimeImmutable('2025-01-01 12:00:00');
        $range = DateRange::fromDateTimes($start, $end);

        self::assertTrue($range->contains($start));
        self::assertTrue($range->contains($end));
        self::assertFalse($range->contains(new \DateTimeImmutable('2025-01-01 09:59:59')));
    }

    public function testOverlaps(): void
    {
        $rangeA = DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            new \DateTimeImmutable('2025-01-01 12:00:00')
        );
        $rangeB = DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 11:00:00'),
            new \DateTimeImmutable('2025-01-01 13:00:00')
        );
        $rangeC = DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 12:01:00'),
            new \DateTimeImmutable('2025-01-01 13:00:00')
        );

        self::assertTrue($rangeA->overlaps($rangeB));
        self::assertFalse($rangeA->overlaps($rangeC));
    }

    public function testDurationInSeconds(): void
    {
        $range = DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            new \DateTimeImmutable('2025-01-01 12:00:00')
        );

        self::assertSame(7200, $range->durationInSeconds());
    }

    public function testGettersAndMutableInput(): void
    {
        $start = new \DateTime('2025-01-01 10:00:00');
        $end = new \DateTime('2025-01-01 12:00:00');
        $range = DateRange::fromDateTimes($start, $end);

        self::assertSame('2025-01-01 10:00:00', $range->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('2025-01-01 12:00:00', $range->getEnd()->format('Y-m-d H:i:s'));
    }

    public function testInvalidRangeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 12:00:00'),
            new \DateTimeImmutable('2025-01-01 10:00:00')
        );
    }
}
