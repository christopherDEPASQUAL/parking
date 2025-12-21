<?php declare(strict_types=1);

namespace App\Application\UseCase\Stationnements;

use App\Application\DTO\Stationnements\ListParkingStationnementsRequest;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class ListParkingStationnements
{
    public function __construct(private readonly StationnementRepositoryInterface $stationnementRepository) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(ListParkingStationnementsRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $sessions = $this->stationnementRepository->listByParking($parkingId, $request->from, $request->to);

        return $this->mapSessions($sessions);
    }

    /**
     * @param array<int, \App\Domain\Entity\ParkingSession> $sessions
     * @return array<int, array<string, mixed>>
     */
    private function mapSessions(array $sessions): array
    {
        $items = [];
        foreach ($sessions as $session) {
            $items[] = [
                'session_id' => $session->getId()->getValue(),
                'parking_id' => $session->getParkingId()->getValue(),
                'user_id' => $session->getUserId()->getValue(),
                'reservation_id' => $session->getReservationId()?->getValue(),
                'abonnement_id' => $session->getAbonnementId()?->getValue(),
                'started_at' => $session->getStartedAt()->format(DATE_ATOM),
                'ended_at' => $session->getEndedAt()?->format(DATE_ATOM),
                'amount_cents' => $session->getAmount()?->getAmountInCents(),
                'currency' => $session->getAmount()?->getCurrency(),
            ];
        }

        return $items;
    }
}
