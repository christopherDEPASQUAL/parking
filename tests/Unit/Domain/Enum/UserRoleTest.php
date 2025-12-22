<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserRoleTest extends TestCase
{
    public function testRoleValues(): void
    {
        self::assertSame('admin', UserRole::ADMIN->value);
        self::assertSame('client', UserRole::CLIENT->value);
        self::assertSame('proprietor', UserRole::PROPRIETOR->value);
    }
}
