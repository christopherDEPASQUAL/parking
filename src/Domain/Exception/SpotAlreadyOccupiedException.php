<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Raised when attempting to occupy an already occupied spot.
 */
class SpotAlreadyOccupiedException extends \DomainException {}