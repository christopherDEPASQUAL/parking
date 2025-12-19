<?php declare(strict_types=1);

namespace App\Application\UseCase\Abonnements;

use App\Application\DTO\Abonnements\SubscribeToAbonnementRequest;
use App\Application\DTO\Abonnements\SubscribeToAbonnementResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\Abonnement;
use App\Domain\Exception\ReservationNotAvailableException;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class SubscribeToAbonnement
{
    public function __construct(
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(SubscribeToAbonnementRequest $request): SubscribeToAbonnementResponse
    {
        $userId = UserId::fromString($request->userId);
        $parkingId = ParkingId::fromString($request->parkingId);
        $startDate = new DateTimeImmutable($request->startDate);
        $endDate = new DateTimeImmutable($request->endDate);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new ValidationException('Utilisateur introuvable.');
        }

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $current = $startDate;
        while ($current <= $endDate) {
            $context = $this->parkingRepository->getAvailabilityContext($parkingId, $current);
            $reservations = $context['reservations'] ?? [];
            $abonnements = $context['abonnements'] ?? [];
            $stationnements = $context['stationnements'] ?? [];

            foreach ($request->weeklyTimeSlots as $slot) {
                $testTime = $current->setTime(
                    (int) explode(':', $slot['start'])[0],
                    (int) explode(':', $slot['start'])[1]
                );

                if ($parking->freeSpotsAt($testTime, $reservations, $abonnements, $stationnements) <= 0) {
                    throw new ReservationNotAvailableException('Parking complet sur le créneau demandé.');
                }
            }

            $current = $current->modify('+1 day');
        }

        $abonnement = new Abonnement(
            AbonnementId::generate(),
            $userId,
            $parkingId,
            $request->weeklyTimeSlots,
            $startDate,
            $endDate
        );

        $this->abonnementRepository->save($abonnement);

        return new SubscribeToAbonnementResponse(
            $abonnement->id()->getValue(),
            $abonnement->status()
        );
    }
}

