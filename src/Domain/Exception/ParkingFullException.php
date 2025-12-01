<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Raised when a parking has reached its maximum capacity.
 */
class ParkingFullException extends \DomainException {}