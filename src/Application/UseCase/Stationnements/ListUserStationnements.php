<?php declare(strict_types=1);

namespace App\Application\UseCase\Stationnements;

use App\Application\DTO\Stationnements\ListUserStationnementsRequest;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\UserId;

final class ListUserStationnements
{
    public function __construct(private readonly StationnementRepositoryInterface $stationnementRepository) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(ListUserStationnementsRequest $request): array
    {
        $userId = UserId::fromString($request->userId);
        $sessions = $this->stationnementRepository->listByUser($userId);

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
