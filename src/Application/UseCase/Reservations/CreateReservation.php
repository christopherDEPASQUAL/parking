<?php declare(strict_types=1);

namespace App\Application\UseCase\Reservations;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\DTO\Reservations\CreateReservationRequest;
use App\Application\DTO\Reservations\CreateReservationResponse;
use App\Application\Exception\ValidationException;
use App\Application\Port\Messaging\EventDispatcherInterface;
use App\Application\UseCase\Payments\ProcessPayment;
use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\Event\ReservationCreated;
use App\Domain\Exception\ReservationNotAvailableException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use DateInterval;

/**
 * Cas d'usage : creation d'une reservation avec verification de disponibilite.
 */
final class CreateReservation
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProcessPayment $processPayment
    ) {
    }

    public function execute(CreateReservationRequest $request): CreateReservationResponse
    {
        $range = DateRange::fromDateTimes($request->startsAt, $request->endsAt);
        $parkingId = ParkingId::fromString($request->parkingId);
        $userId = UserId::fromString($request->userId);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new ValidationException('Utilisateur introuvable.');
        }

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        if (!$parking->isOpenAt($range->getStart()) || !$parking->isOpenAt($range->getEnd())) {
            throw new ReservationNotAvailableException('Le parking est ferme sur ce creneau.');
        }

        if ($this->reservationRepository->hasUserOverlap($userId, $range, $parkingId)) {
            throw new ReservationNotAvailableException('Une reservation existante chevauche ce creneau pour cet utilisateur.');
        }

        $step = new DateInterval('PT15M');
        $cursor = $range->getStart();
        while ($cursor <= $range->getEnd()) {
            $context = $this->parkingRepository->getAvailabilityContext($parkingId, $cursor);
            $reservations = $context['reservations'] ?? [];
            $abonnements = $context['abonnements'] ?? [];
            $stationnements = $context['stationnements'] ?? [];

            if ($parking->freeSpotsAt($cursor, $reservations, $abonnements, $stationnements) <= 0) {
                throw new ReservationNotAvailableException('Parking complet sur le creneau demande.');
            }

            $cursor = $cursor->add($step);
        }

        $minutes = (int) ceil($range->durationInSeconds() / 60);
        $priceCents = $parking->computePriceForDurationMinutes($minutes);
        $price = Money::fromCents($priceCents);

        $reservation = new Reservation(
            ReservationId::generate(),
            $userId,
            $parkingId,
            $range,
            $price,
            ReservationStatus::PENDING_PAYMENT
        );

        $this->reservationRepository->save($reservation);

        $payment = $this->processPayment->execute(new ChargeRequest(
            $userId->getValue(),
            $priceCents,
            $price->getCurrency(),
            $reservation->id()->getValue()
        ));

        if ($payment->status()->isApproved()) {
            $reservation->confirm();
        } else {
            $reservation->markPaymentFailed();
        }

        $this->reservationRepository->save($reservation);
        $this->eventDispatcher->dispatch(
            new ReservationCreated(
                $reservation->id(),
                $userId,
                $parkingId,
                $range,
                $price
            )
        );

        return new CreateReservationResponse(
            $reservation->id()->getValue(),
            $reservation->status()->value,
            $priceCents,
            $price->getCurrency(),
            $range->getStart(),
            $range->getEnd()
        );
    }
}
