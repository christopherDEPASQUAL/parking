<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalizationAndEquals(): void
    {
        $email = Email::fromString(' Test@Example.com ');
        $same = Email::fromString('test@example.com');

        self::assertSame('test@example.com', $email->getValue());
        self::assertTrue($email->equals($same));
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('not-an-email');
    }
}
