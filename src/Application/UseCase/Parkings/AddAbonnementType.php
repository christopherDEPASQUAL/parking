<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\AddAbonnementTypeRequest;
use App\Application\DTO\Parkings\AddAbonnementTypeResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\Abonnement;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class AddAbonnementType
{
    public function __construct(
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(AddAbonnementTypeRequest $request): AddAbonnementTypeResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $ownerId = UserId::fromString($request->ownerId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        if (!$parking->getUserId()->equals($ownerId)) {
            throw new ValidationException('Vous n\'êtes pas propriétaire de ce parking.');
        }

        $owner = $this->userRepository->findById($ownerId);
        if ($owner === null) {
            throw new ValidationException('Propriétaire introuvable.');
        }

        $startDate = new DateTimeImmutable();
        $endDate = $startDate->modify('+1 year');

        $abonnement = new Abonnement(
            AbonnementId::generate(),
            $ownerId,
            $parkingId,
            $request->weeklyTimeSlots,
            $startDate,
            $endDate,
            'active'
        );

        $this->abonnementRepository->save($abonnement);
        $parking->attachAbonnement($abonnement->id());
        $this->parkingRepository->save($parking);

        return new AddAbonnementTypeResponse($abonnement->id()->getValue());
    }
}

