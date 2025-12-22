<?php declare(strict_types=1);

namespace App\Application\UseCase\Stationnements;

use App\Application\DTO\Stationnements\EnterParkingRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;

final class EnterParking
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly StationnementRepositoryInterface $stationnementRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * @return array{session_id:string,started_at:string}
     */
    public function execute(EnterParkingRequest $request): array
    {
        $at = $request->at ?? new \DateTimeImmutable();
        $parkingId = ParkingId::fromString($request->parkingId);
        $userId = UserId::fromString($request->userId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        if ($this->userRepository->findById($userId) === null) {
            throw new ValidationException('User not found.');
        }

        if ($this->stationnementRepository->findActiveByUser($userId, $parkingId) !== null) {
            throw new ValidationException('User already has an active session in this parking.');
        }

        if (($request->reservationId === null && $request->abonnementId === null)
            || ($request->reservationId !== null && $request->abonnementId !== null)) {
            throw new ValidationException('Provide exactly one of reservation_id or abonnement_id.');
        }

        $reservationId = null;
        $abonnementId = null;

        if ($request->reservationId !== null) {
            $reservationId = ReservationId::fromString($request->reservationId);
            $reservation = $this->reservationRepository->findById($reservationId);

            if ($reservation === null) {
                throw new ValidationException('Reservation not found.');
            }

            if (!$reservation->userId()->equals($userId) || !$reservation->parkingId()->equals($parkingId)) {
                throw new ValidationException('Reservation does not match user or parking.');
            }

            if (!$reservation->isEntryAllowedAt($at)) {
                throw new ValidationException('Reservation is not confirmed for this time.');
            }
        } else {
            $abonnementId = AbonnementId::fromString($request->abonnementId);
            $abonnement = $this->abonnementRepository->findById($abonnementId);

            if ($abonnement === null) {
                throw new ValidationException('Abonnement not found.');
            }

            if (!$abonnement->userId()->equals($userId) || !$abonnement->parkingId()->equals($parkingId)) {
                throw new ValidationException('Abonnement does not match user or parking.');
            }

            if (!$abonnement->covers($at)) {
                throw new ValidationException('Abonnement is not valid at this time.');
            }
        }

        if (!$parking->isOpenAt($at)) {
            throw new ValidationException('Parking is closed at this time.');
        }

        $context = $this->parkingRepository->getAvailabilityContext($parkingId, $at);
        if ($parking->freeSpotsAt($at, $context['reservations'], $context['abonnements'], $context['stationnements']) <= 0) {
            throw new ValidationException('Parking is full at this time.');
        }

        $session = ParkingSession::start($parkingId, $userId, $reservationId, $abonnementId, $at);
        $this->stationnementRepository->save($session);

        return [
            'session_id' => $session->getId()->getValue(),
            'started_at' => $session->getStartedAt()->format(DATE_ATOM),
        ];
    }
}
