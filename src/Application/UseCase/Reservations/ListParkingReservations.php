<?php declare(strict_types=1);

namespace App\Application\UseCase\Reservations;

use App\Application\DTO\Reservations\ListParkingReservationsRequest;
use App\Application\DTO\Reservations\ListParkingReservationsResponse;
use App\Domain\Enum\ReservationStatus;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

/**
 * Cas d'usage : lister les réservations d'un parking.
 */
final class ListParkingReservations
{
    public function __construct(private readonly ReservationRepositoryInterface $reservationRepository) {}

    public function execute(ListParkingReservationsRequest $request): ListParkingReservationsResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $status = $request->status ? ReservationStatus::from(strtoupper($request->status)) : null;

        $offset = max(0, ($request->page - 1) * $request->perPage);
        $reservations = $this->reservationRepository->listByParking(
            $parkingId,
            $status,
            $request->from,
            $request->to,
            $request->perPage,
            $offset
        );

        $items = [];
        foreach ($reservations as $reservation) {
            $items[] = [
                'reservationId' => (string) $reservation->id(),
                'userId' => (string) $reservation->userId(),
                'status' => $reservation->status()->value,
                'startsAt' => $reservation->dateRange()->getStart(),
                'endsAt' => $reservation->dateRange()->getEnd(),
                'priceCents' => $reservation->price()->getAmountInCents(),
                'currency' => $reservation->price()->getCurrency(),
            ];
        }

        $total = $this->reservationRepository->countByParking(
            $parkingId,
            $status,
            $request->from,
            $request->to
        );

        return new ListParkingReservationsResponse(
            $items,
            $request->page,
            $request->perPage,
            $total
        );
    }
}
