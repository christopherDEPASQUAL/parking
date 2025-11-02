<?php declare(strict_types=1);

namespace App\Reservations;

/**
 * Use case: Create reservation, check availability, persist, and dispatch event.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class CreateReservation {}
