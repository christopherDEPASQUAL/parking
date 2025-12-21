<?php declare(strict_types=1);

namespace App\Infrastructure\Time;

use App\Application\Port\Services\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
