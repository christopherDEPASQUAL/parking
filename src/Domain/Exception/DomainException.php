<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Base class for all domain exceptions with optional context payload.
 */
abstract class DomainException extends \DomainException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $message, private readonly array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}

