<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\PasswordHash;
use PHPUnit\Framework\TestCase;

final class PasswordHashTest extends TestCase
{
    public function testHashAndVerify(): void
    {
        $hash = PasswordHash::fromPlainText('password123');

        self::assertTrue($hash->verify('password123'));
        self::assertFalse($hash->verify('wrong-password'));
    }

    public function testTooShortPasswordThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PasswordHash::fromPlainText('short');
    }

    public function testEquals(): void
    {
        $hash = PasswordHash::fromPlainText('password123');
        $same = PasswordHash::fromHash($hash->getHash());

        self::assertTrue($hash->equals($same));
    }

    public function testToStringReturnsHash(): void
    {
        $hash = PasswordHash::fromPlainText('password123');

        self::assertSame($hash->getHash(), (string) $hash);
    }
}
