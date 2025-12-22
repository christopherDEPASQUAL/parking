<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\ListOverstayedDriversRequest;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class ListOverstayedDrivers
{
    public function __construct(
        private readonly StationnementRepositoryInterface $stationnementRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(ListOverstayedDriversRequest $request): array
    {
        $at = $request->at ?? new \DateTimeImmutable();
        $parkingId = ParkingId::fromString($request->parkingId);

        $sessions = $this->stationnementRepository->listActiveAt($parkingId, $at);
        $items = [];

        foreach ($sessions as $session) {
            $isValid = false;

            if ($session->getReservationId() !== null) {
                $reservation = $this->reservationRepository->findById($session->getReservationId());
                $isValid = $reservation !== null && $reservation->isActiveAt($at);
            } elseif ($session->getAbonnementId() !== null) {
                $abonnement = $this->abonnementRepository->findById($session->getAbonnementId());
                $isValid = $abonnement !== null && $abonnement->covers($at);
            }

            if ($isValid) {
                continue;
            }

            $items[] = [
                'session_id' => $session->getId()->getValue(),
                'user_id' => $session->getUserId()->getValue(),
                'parking_id' => $session->getParkingId()->getValue(),
                'reservation_id' => $session->getReservationId()?->getValue(),
                'abonnement_id' => $session->getAbonnementId()?->getValue(),
                'started_at' => $session->getStartedAt()->format(DATE_ATOM),
            ];
        }

        return $items;
    }
}
