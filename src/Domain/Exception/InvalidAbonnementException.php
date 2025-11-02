<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when subscription rules are violated (period/eligibility).
 *
 * Scope:
 *  - Domain invariants only (not technical failures).
 */
class InvalidAbonnementException extends \DomainException {}
