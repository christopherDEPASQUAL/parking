<?php declare(strict_types=1);

namespace App\Application\Port\Services;

/**
 * Port: provide current time (testable clock).
 *
 * Implementations:
 *  - Infrastructure layer.
 */
interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
