<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testFromCentsNormalizesCurrency(): void
    {
        $money = Money::fromCents(150, 'usd');

        self::assertSame(150, $money->getAmountInCents());
        self::assertSame('USD', $money->getCurrency());
    }

    public function testFromFloatRoundsToCents(): void
    {
        $money = Money::fromFloat(10.235, 'eur');

        self::assertSame(1024, $money->getAmountInCents());
    }

    public function testAddSubtractMultiply(): void
    {
        $base = Money::fromCents(1000, 'EUR');
        $extra = Money::fromCents(250, 'EUR');

        self::assertSame(1250, $base->add($extra)->getAmountInCents());
        self::assertSame(750, $base->subtract($extra)->getAmountInCents());
        self::assertSame(2000, $base->multiply(2)->getAmountInCents());
    }

    public function testCurrencyMismatchThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromCents(100, 'EUR')->add(Money::fromCents(100, 'USD'));
    }

    public function testEqualsAndToFloat(): void
    {
        $money = Money::fromCents(1234, 'EUR');
        $same = Money::fromCents(1234, 'EUR');

        self::assertTrue($money->equals($same));
        self::assertSame(12.34, $money->toFloat());
    }
}
