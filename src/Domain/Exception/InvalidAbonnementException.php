<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when subscription rules are violated (period/eligibility).
 */
final class InvalidAbonnementException extends DomainException
{
    public static function dueToOverlappingSlots(): self
    {
        return new self('Subscription slots cannot overlap.');
    }

    public static function dueToExpiredPeriod(): self
    {
        return new self('Subscription period cannot be in the past.');
    }

    public static function dueToInvalidDuration(): self
    {
        return new self('Subscription duration must be at least one month.');
    }
}
