<?php declare(strict_types=1);

namespace App\Auth;

/**
 * Use case: Register new user with validation and password hashing. Input/Output DTOs.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class RegisterUser {}
