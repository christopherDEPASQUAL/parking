<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Raised when attempting to add a parking spot that already exists.
 */
class SpotAlreadyExistsException extends \DomainException {}
