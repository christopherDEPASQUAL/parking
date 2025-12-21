<?php declare(strict_types=1);

namespace App\Application\UseCase\Abonnements;

use App\Application\DTO\Abonnements\ListUserAbonnementsRequest;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\UserId;

final class ListUserAbonnements
{
    public function __construct(
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly SubscriptionOfferRepositoryInterface $offerRepository
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(ListUserAbonnementsRequest $request): array
    {
        $userId = UserId::fromString($request->userId);
        $abonnements = $this->abonnementRepository->listByUser($userId, $request->status);

        $items = [];
        foreach ($abonnements as $abonnement) {
            $offer = $this->offerRepository->findById($abonnement->offerId());
            $items[] = [
                'abonnement_id' => $abonnement->id()->getValue(),
                'user_id' => $abonnement->userId()->getValue(),
                'parking_id' => $abonnement->parkingId()->getValue(),
                'offer_id' => $abonnement->offerId()->getValue(),
                'offer_label' => $offer?->label(),
                'offer_type' => $offer?->type(),
                'offer_price_cents' => $offer?->priceCents(),
                'weekly_time_slots' => $abonnement->weeklyTimeSlots(),
                'start_date' => $abonnement->startDate()->format(DATE_ATOM),
                'end_date' => $abonnement->endDate()->format(DATE_ATOM),
                'status' => $abonnement->status(),
            ];
        }

        return $items;
    }
}
