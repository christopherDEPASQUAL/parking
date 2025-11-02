<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * PasswordHash value object that encapsulates hashed secret (never plain).
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class PasswordHash {}
