<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when attempting to book an occupied/unavailable slot.
 *
 * Scope:
 *  - Domain invariants only (not technical failures).
 */
class ReservationNotAvailableException extends \DomainException {}
