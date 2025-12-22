<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\SubscriptionOffer;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;

interface SubscriptionOfferRepositoryInterface
{
    public function save(SubscriptionOffer $offer): void;

    public function findById(SubscriptionOfferId $id): ?SubscriptionOffer;

    /**
     * @return SubscriptionOffer[]
     */
    public function listByParking(ParkingId $parkingId, ?string $status = null): array;
}
