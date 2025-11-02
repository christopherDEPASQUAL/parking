<?php declare(strict_types=1);

namespace App\Users;

/**
 * Use case: Return a profile projection for UI/API consumption.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class GetUserProfile {}
