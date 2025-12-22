<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\OpeningSchedule;
use PHPUnit\Framework\TestCase;

final class OpeningScheduleTest extends TestCase
{
    public function testAlwaysOpen(): void
    {
        $schedule = OpeningSchedule::alwaysOpen();

        self::assertTrue($schedule->isOpenAt(new \DateTimeImmutable('2025-01-01 03:00:00')));
        self::assertTrue($schedule->isOpenAt(new \DateTimeImmutable('2025-01-04 23:59:00')));
    }

    public function testDailyMapInput(): void
    {
        $schedule = new OpeningSchedule([
            1 => [
                ['start' => '08:00', 'end' => '10:00'],
            ],
        ]);

        self::assertTrue($schedule->isOpenAt(new \DateTimeImmutable('2025-01-06 09:00:00')));
        self::assertFalse($schedule->isOpenAt(new \DateTimeImmutable('2025-01-06 11:00:00')));
    }

    public function testOvernightSlot(): void
    {
        $schedule = new OpeningSchedule([
            [
                'start_day' => 5,
                'end_day' => 5,
                'start_time' => '22:00',
                'end_time' => '02:00',
            ],
        ]);

        self::assertTrue($schedule->isOpenAt(new \DateTimeImmutable('2025-01-03 23:00:00')));
        self::assertTrue($schedule->isOpenAt(new \DateTimeImmutable('2025-01-04 01:00:00')));
    }

    public function testOverlappingSlotsThrow(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpeningSchedule([
            [
                'start_day' => 1,
                'end_day' => 1,
                'start_time' => '08:00',
                'end_time' => '12:00',
            ],
            [
                'start_day' => 1,
                'end_day' => 1,
                'start_time' => '11:00',
                'end_time' => '14:00',
            ],
        ]);
    }

    public function testToArrayReturnsNormalizedSlots(): void
    {
        $schedule = new OpeningSchedule([
            [
                'start_day' => 2,
                'end_day' => 2,
                'start_time' => '09:00',
                'end_time' => '11:00',
            ],
        ]);

        $slots = $schedule->toArray();

        self::assertSame(2, $slots[0]['start_day']);
        self::assertSame('09:00', $slots[0]['start_time']);
    }

    public function testLegacySlotFormat(): void
    {
        $schedule = new OpeningSchedule([
            ['day' => 3, 'start' => '10:00', 'end' => '12:00'],
        ]);

        self::assertTrue($schedule->isOpenAt(new \DateTimeImmutable('2025-01-08 10:30:00')));
    }

    public function testInvalidDayThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpeningSchedule([
            [
                'start_day' => 7,
                'end_day' => 7,
                'start_time' => '08:00',
                'end_time' => '10:00',
            ],
        ]);
    }

    public function testInvalidEndTimeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpeningSchedule([
            [
                'start_day' => 1,
                'end_day' => 1,
                'start_time' => '08:00',
                'end_time' => '24:30',
            ],
        ]);
    }

    public function testZeroDurationThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OpeningSchedule([
            [
                'start_day' => 1,
                'end_day' => 1,
                'start_time' => '08:00',
                'end_time' => '08:00',
            ],
        ]);
    }
}
