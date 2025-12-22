<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class PaymentStatusTest extends TestCase
{
    public function testIsApproved(): void
    {
        self::assertTrue(PaymentStatus::APPROVED->isApproved());
        self::assertFalse(PaymentStatus::PENDING->isApproved());
    }
}
