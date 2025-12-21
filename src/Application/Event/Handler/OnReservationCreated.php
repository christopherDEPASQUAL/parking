<?php declare(strict_types=1);

namespace App\Application\Event\Handler;

use App\Domain\Event\ReservationCreated;
use App\Infrastructure\Logging\SimpleLogger;

/**
 * Handler application invoqué quand une réservation est créée.
 * Ici on se contente d'un stub, à implémenter (projection, notification, etc.).
 */
final class OnReservationCreated
{
    public function __construct(private readonly SimpleLogger $logger) {}

    public function __invoke(ReservationCreated $event): void
    {
        $this->logger->info('reservation.created', [
            'reservation_id' => $event->reservationId()->getValue(),
            'user_id' => $event->userId()->getValue(),
            'parking_id' => $event->parkingId()->getValue(),
            'starts_at' => $event->dateRange()->getStart()->format(DATE_ATOM),
            'ends_at' => $event->dateRange()->getEnd()->format(DATE_ATOM),
            'price_cents' => $event->price()->getAmountInCents(),
            'currency' => $event->price()->getCurrency(),
        ]);
    }
}
