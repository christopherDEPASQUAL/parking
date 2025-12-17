<?php declare(strict_types=1);

namespace App\Application\Event\Handler;

use App\Domain\Event\ReservationCreated;

/**
 * Handler application invoqué quand une réservation est créée.
 * Ici on se contente d'un stub, à implémenter (projection, notification, etc.).
 */
final class OnReservationCreated
{
    public function __invoke(ReservationCreated $event): void
    {
        // TODO: notifier / projeter / facturer
    }
}
