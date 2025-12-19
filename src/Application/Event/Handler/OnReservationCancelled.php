<?php declare(strict_types=1);

namespace App\Application\Event\Handler;

use App\Domain\Event\ReservationCancelled;

/**
 * Handler application invoqué quand une réservation est annulée.
 */
final class OnReservationCancelled
{
    public function __invoke(ReservationCancelled $event): void
    {
        // TODO: notifier / libérer des ressources / projeter
    }
}
