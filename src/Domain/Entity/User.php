<?php declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Domain Entity: User
 *
 * Purpose:
 *  - Represents an application user (client/proprietor).
 *  - Holds invariant-protected properties (id, email, role, password hash VO).
 *
 * Notes:
 *  - No persistence/HTTP logic.
 *  - Emits no side effects other than recording domain events if needed.
 */
final class User {}
