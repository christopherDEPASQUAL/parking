<?php declare(strict_types=1);

namespace App\Application\UseCase\SubscriptionOffers;

use App\Application\DTO\SubscriptionOffers\ListParkingSubscriptionOffersRequest;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class ListParkingSubscriptionOffers
{
    public function __construct(private readonly SubscriptionOfferRepositoryInterface $offerRepository) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(ListParkingSubscriptionOffersRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $offers = $this->offerRepository->listByParking($parkingId, $request->status);

        $items = [];
        foreach ($offers as $offer) {
            $items[] = [
                'offer_id' => $offer->id()->getValue(),
                'parking_id' => $offer->parkingId()->getValue(),
                'label' => $offer->label(),
                'type' => $offer->type(),
                'price_cents' => $offer->priceCents(),
                'status' => $offer->status(),
                'weekly_time_slots' => $offer->weeklyTimeSlots(),
            ];
        }

        return $items;
    }
}
