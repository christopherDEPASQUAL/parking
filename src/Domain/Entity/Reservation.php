<?php declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Domain Entity: Reservation
 *
 * Purpose:
 *  - Represents a booking for a parking slot between a date range.
 *  - Guards state transitions via ReservationStatus enum.
 *  - May record ReservationCreated/Cancelled events.
 */
final class Reservation {}
