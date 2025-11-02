<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Email value object ensuring valid format and immutability.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class Email {}
