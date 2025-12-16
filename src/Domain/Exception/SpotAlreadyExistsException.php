<?php declare(strict_types=1);

namespace App\Domain\Exception;

final class SpotAlreadyExistsException extends DomainException
{
    public static function forIdentifier(string $spotIdentifier): self
    {
        return new self(
            sprintf('Spot "%s" already exists.', $spotIdentifier),
            ['spot' => $spotIdentifier]
        );
    }
}
