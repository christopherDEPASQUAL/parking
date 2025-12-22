<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\ExitParkingRequest;
use App\Application\DTO\Parkings\ExitParkingResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class ExitParking
{
    private const PENALTY_CENTS = 2000;

    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly ParkingSessionRepositoryInterface $sessionRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository
    ) {}

    public function execute(ExitParkingRequest $request): ExitParkingResponse
    {
        $userId = UserId::fromString($request->userId);
        $parkingId = ParkingId::fromString($request->parkingId);
        $exitedAt = $request->exitedAt 
            ? new DateTimeImmutable($request->exitedAt) 
            : new DateTimeImmutable();

        $session = $this->sessionRepository->findActiveByUserAndParking($userId, $parkingId);
        if ($session === null) {
            throw new ValidationException('Aucun stationnement actif trouvé pour cet utilisateur dans ce parking.');
        }

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $session->close($exitedAt);
        $this->sessionRepository->save($session);

        $durationMinutes = $session->durationMinutes($exitedAt);
        $basePriceCents = $parking->computePriceForDurationMinutes($durationMinutes);
        $penaltyCents = $this->calculatePenalty($userId, $parkingId, $session->getStartedAt(), $exitedAt);
        $totalPriceCents = $basePriceCents + $penaltyCents;

        return new ExitParkingResponse(
            $session->getId()->getValue(),
            $durationMinutes,
            $basePriceCents,
            $penaltyCents,
            $totalPriceCents,
            $exitedAt->format('Y-m-d H:i:s')
        );
    }

    private function calculatePenalty(UserId $userId, ParkingId $parkingId, DateTimeImmutable $startedAt, DateTimeImmutable $endedAt): int
    {
        $reservations = $this->reservationRepository->listActiveAt($parkingId, $startedAt);
        
        foreach ($reservations as $reservation) {
            if ($reservation->userId()->equals($userId)) {
                $dateRange = $reservation->dateRange();
                if ($startedAt < $dateRange->start() || $endedAt > $dateRange->end()) {
                    return self::PENALTY_CENTS;
                }
            }
        }

        $abonnements = $this->abonnementRepository->listByUser($userId);
        
        foreach ($abonnements as $abonnement) {
            if ($abonnement->parkingId()->equals($parkingId)) {
                if (!$abonnement->coversTimeSlot($startedAt) || !$abonnement->coversTimeSlot($endedAt)) {
                    return self::PENALTY_CENTS;
                }
            }
        }

        return 0;
    }
}
