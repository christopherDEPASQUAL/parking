<?php declare(strict_types=1);

namespace App\Domain\Exception;

final class SessionAlreadyClosedException extends DomainException
{
    public static function forSession(string $sessionId): self
    {
        return new self(
            sprintf('Session "%s" is already closed.', $sessionId),
            ['session_id' => $sessionId]
        );
    }
}
