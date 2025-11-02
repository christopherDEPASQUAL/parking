<?php declare(strict_types=1);

namespace App\Auth;

/**
 * Use case: Authenticate user and issue JWT (via ports). Input: LoginUserRequest, Output: LoginUserResponse.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class LoginUser {}
