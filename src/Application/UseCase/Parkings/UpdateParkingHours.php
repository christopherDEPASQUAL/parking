<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\UpdateParkingHoursRequest;
use App\Application\DTO\Parkings\UpdateParkingHoursResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Exception\UnauthorizedActionException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;

/**
 * Cas d'usage : Modifier les horaires d'ouverture d'un parking.
 */
final class UpdateParkingHours
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function execute(UpdateParkingHoursRequest $request): UpdateParkingHoursResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $ownerId = UserId::fromString($request->ownerId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        // Vérifier que l'utilisateur est bien le propriétaire
        if (!$parking->getUserId()->equals($ownerId)) {
            throw new UnauthorizedActionException('Seul le propriétaire peut modifier les horaires du parking.');
        }

        // Créer le nouveau planning d'ouverture
        $openingSchedule = empty($request->openingHours)
            ? OpeningSchedule::alwaysOpen()
            : new OpeningSchedule($request->openingHours);

        // Mettre à jour
        $parking->changeOpeningSchedule($openingSchedule);
        $this->parkingRepository->save($parking);

        return new UpdateParkingHoursResponse(
            $parkingId->getValue(),
            $this->serializeOpeningSchedule($openingSchedule)
        );
    }

    private function serializeOpeningSchedule(OpeningSchedule $schedule): array
    {
        // TODO: Implémenter la sérialisation de l'OpeningSchedule
        // Pour l'instant, retourner un tableau vide
        return [];
    }
}

