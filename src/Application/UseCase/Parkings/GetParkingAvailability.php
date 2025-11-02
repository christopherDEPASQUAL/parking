<?php declare(strict_types=1);

namespace App\Parkings;

/**
 * Use case: Compute availability for a given DateRange using repositories/services.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class GetParkingAvailability {}
