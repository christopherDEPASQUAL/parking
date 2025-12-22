<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\TimeSlot;
use PHPUnit\Framework\TestCase;

final class TimeSlotTest extends TestCase
{
    public function testFromStringsAndToString(): void
    {
        $slot = TimeSlot::fromStrings('08:00', '10:00');

        self::assertSame('08:00-10:00', (string) $slot);
        self::assertTrue($slot->isWithin(new \DateTimeImmutable('2025-01-01 09:00:00')));
        self::assertFalse($slot->isWithin(new \DateTimeImmutable('2025-01-01 10:00:00')));
    }

    public function testOverlaps(): void
    {
        $a = TimeSlot::fromStrings('08:00', '10:00');
        $b = TimeSlot::fromStrings('09:00', '11:00');
        $c = TimeSlot::fromStrings('10:00', '12:00');

        self::assertTrue($a->overlaps($b));
        self::assertFalse($a->overlaps($c));
    }

    public function testInvalidFormatThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeSlot::fromStrings('8am', '10:00');
    }

    public function testInvalidRangeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeSlot::fromStrings('10:00', '10:00');
    }

    public function testGetters(): void
    {
        $slot = TimeSlot::fromStrings('08:00', '09:30');

        self::assertSame(480, $slot->getStartMinutes());
        self::assertSame(570, $slot->getEndMinutes());
    }

    public function testFromMinutes(): void
    {
        $slot = TimeSlot::fromMinutes(480, 600);

        self::assertSame('08:00-10:00', (string) $slot);
    }
}
