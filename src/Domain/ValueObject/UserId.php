<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Strongly-typed identifier for User aggregate.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class UserId {}
