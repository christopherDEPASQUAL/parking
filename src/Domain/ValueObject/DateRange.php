<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * DateRange immutable object (start/end) with overlap checks.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class DateRange {}
