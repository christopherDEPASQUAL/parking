<?php declare(strict_types=1);

namespace App\Application\UseCase\Abonnements;

use App\Application\DTO\Abonnements\ListParkingAbonnementsRequest;
use App\Application\DTO\Abonnements\ListParkingAbonnementsResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class ListParkingAbonnements
{
    public function __construct(
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function execute(ListParkingAbonnementsRequest $request): ListParkingAbonnementsResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $abonnements = $this->abonnementRepository->listByParking($parkingId);

        $result = [];
        foreach ($abonnements as $abonnement) {
            $result[] = [
                'id' => $abonnement->id()->getValue(),
                'userId' => $abonnement->userId()->getValue(),
                'parkingId' => $abonnement->parkingId()->getValue(),
                'weeklyTimeSlots' => $abonnement->weeklyTimeSlots(),
                'startDate' => $abonnement->startDate()->format('Y-m-d'),
                'endDate' => $abonnement->endDate()->format('Y-m-d'),
                'status' => $abonnement->status()
            ];
        }

        return new ListParkingAbonnementsResponse($result);
    }
}

