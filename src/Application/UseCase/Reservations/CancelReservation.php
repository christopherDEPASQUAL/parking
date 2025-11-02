<?php declare(strict_types=1);

namespace App\Reservations;

/**
 * Use case: Cancel reservation, update status, dispatch cancellation event.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class CancelReservation {}
