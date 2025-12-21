<?php declare(strict_types=1);

namespace App\Application\UseCase\Abonnements;

use App\Application\DTO\Abonnements\CreateAbonnementRequest;
use App\Application\DTO\Payments\ChargeRequest;
use App\Application\Exception\ValidationException;
use App\Application\UseCase\Payments\ProcessPayment;
use App\Domain\Entity\Abonnement;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
use App\Domain\ValueObject\UserId;

final class CreateAbonnement
{
    public function __construct(
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SubscriptionOfferRepositoryInterface $offerRepository,
        private readonly ProcessPayment $processPayment
    ) {}

    /**
     * @return array{abonnement_id:string,status:string,start_date:string,end_date:string}
     */
    public function execute(CreateAbonnementRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $userId = UserId::fromString($request->userId);
        $offerId = SubscriptionOfferId::fromString($request->offerId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        if ($this->userRepository->findById($userId) === null) {
            throw new ValidationException('User not found.');
        }

        $offer = $this->offerRepository->findById($offerId);
        if ($offer === null) {
            throw new ValidationException('Subscription offer not found.');
        }
        if (!$offer->parkingId()->equals($parkingId)) {
            throw new ValidationException('Offer does not match parking.');
        }
        if ($offer->status() !== 'active') {
            throw new ValidationException('Offer is not available.');
        }

        $status = $request->status ?? 'active';
        $abonnement = new Abonnement(
            AbonnementId::generate(),
            $userId,
            $parkingId,
            $offerId,
            $offer->weeklyTimeSlots(),
            $request->startDate,
            $request->endDate,
            $status
        );

        $this->abonnementRepository->save($abonnement);

        $priceCents = $offer->priceCents();
        $payment = $this->processPayment->execute(new ChargeRequest(
            $userId->getValue(),
            $priceCents,
            'EUR',
            null,
            $abonnement->id()->getValue()
        ));

        if (!$payment->status()->isApproved()) {
            $abonnement->suspend();
            $this->abonnementRepository->save($abonnement);
        }

        return [
            'abonnement_id' => $abonnement->id()->getValue(),
            'offer_id' => $offerId->getValue(),
            'status' => $abonnement->status(),
            'start_date' => $abonnement->startDate()->format(DATE_ATOM),
            'end_date' => $abonnement->endDate()->format(DATE_ATOM),
        ];
    }
}
