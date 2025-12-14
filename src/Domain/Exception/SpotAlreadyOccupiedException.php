<?php declare(strict_types=1);

namespace App\Domain\Exception;

final class SpotAlreadyOccupiedException extends DomainException
{
    public static function forIdentifier(string $spotIdentifier): self
    {
        return new self(
            sprintf('Spot "%s" is already occupied.', $spotIdentifier),
            ['spot' => $spotIdentifier]
        );
    }
}
