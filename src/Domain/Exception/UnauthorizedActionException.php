<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when actor cannot perform the requested domain action.
 *
 * Scope:
 *  - Domain invariants only (not technical failures).
 */
class UnauthorizedActionException extends \DomainException {}
