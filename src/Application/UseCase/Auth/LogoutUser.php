<?php declare(strict_types=1);

namespace App\Auth;

/**
 * Use case: Invalidate refresh token / session (if applicable).
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class LogoutUser {}
