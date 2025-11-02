<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Money value object (amount + currency) with safe arithmetic policies.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class Money {}
