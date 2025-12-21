<?php declare(strict_types=1);

namespace App\Application\Event\Handler;

use App\Domain\Event\ReservationCancelled;
use App\Infrastructure\Logging\SimpleLogger;

/**
 * Handler application invoked when a reservation is cancelled.
 */
final class OnReservationCancelled
{
    public function __construct(private readonly SimpleLogger $logger) {}

    public function __invoke(ReservationCancelled $event): void
    {
        $this->logger->info('reservation.cancelled', [
            'reservation_id' => $event->reservationId()->getValue(),
            'user_id' => $event->userId()->getValue(),
            'parking_id' => $event->parkingId()->getValue(),
            'reason' => $event->reason(),
            'cancelled_at' => $event->cancelledAt()->format(DATE_ATOM),
        ]);
    }
}
