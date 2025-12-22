<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class ReservationStatusTest extends TestCase
{
    public function testStateHelpers(): void
    {
        self::assertTrue(ReservationStatus::PENDING->canBeCancelled());
        self::assertTrue(ReservationStatus::CONFIRMED->isActive());
        self::assertTrue(ReservationStatus::CONFIRMED->isEntryAllowed());
        self::assertTrue(ReservationStatus::COMPLETED->isCompleted());
        self::assertTrue(ReservationStatus::CANCELLED->isCancelled());
    }
}
