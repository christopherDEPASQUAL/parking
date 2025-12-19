<?php declare(strict_types=1);

namespace App\Application\UseCase\Reservations;

use App\Application\DTO\Reservations\ListUserReservationsRequest;
use App\Application\DTO\Reservations\ListUserReservationsResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;

/**
 * Cas d'usage : Liste des réservations d'un utilisateur.
 */
final class ListUserReservations
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(ListUserReservationsRequest $request): ListUserReservationsResponse
    {
        $userId = UserId::fromString($request->userId);

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new ValidationException('Utilisateur introuvable.');
        }

        $reservations = $this->reservationRepository->listByUser($userId);

        $results = [];
        foreach ($reservations as $reservation) {
            $dateRange = $reservation->dateRange();
            $price = $reservation->price();

            $results[] = [
                'id' => $reservation->id()->getValue(),
                'parkingId' => $reservation->parkingId()->getValue(),
                'status' => $reservation->status()->value,
                'startsAt' => $dateRange->getStart()->format('Y-m-d H:i:s'),
                'endsAt' => $dateRange->getEnd()->format('Y-m-d H:i:s'),
                'priceCents' => $price->getAmountInCents(),
                'currency' => $price->getCurrency(),
            ];
        }

        return new ListUserReservationsResponse($results);
    }
}

