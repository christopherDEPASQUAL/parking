<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Exception;

use App\Domain\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class DomainExceptionTest extends TestCase
{
    public function testContextIsExposed(): void
    {
        $exception = new class('Error', ['foo' => 'bar']) extends DomainException {};

        self::assertSame(['foo' => 'bar'], $exception->getContext());
    }
}
