<?php declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidSessionTimeException extends DomainException
{
    public static function startAfterEnd(): self
    {
        return new self('Session start time must be before end time.');
    }

    public static function negativeDuration(): self
    {
        return new self('Session duration must be positive.');
    }
}
