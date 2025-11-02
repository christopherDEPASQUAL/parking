<?php declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Domain event emitted when a Reservation is successfully created.
 *
 * Notes:
 *  - Pure data (no side effects).
 *  - Handlers live in Application layer.
 */
final class ReservationCreated {}
