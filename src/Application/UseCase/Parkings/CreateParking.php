<?php declare(strict_types=1);

namespace App\Parkings;

/**
 * Use case: Create parking entity; persist via repository; return id.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class CreateParking {}
