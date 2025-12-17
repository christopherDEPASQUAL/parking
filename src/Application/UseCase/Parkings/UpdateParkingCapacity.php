<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\UpdateParkingCapacityRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

/**
 * Cas d'usage : mettre à jour la capacité d'un parking.
 */
final class UpdateParkingCapacity
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    public function execute(UpdateParkingCapacityRequest $request): void
    {
        $id = ParkingId::fromString($request->parkingId);
        $parking = $this->parkingRepository->findById($id);

        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $parking->updateCapacity($request->newCapacity);
        $this->parkingRepository->save($parking);
    }
}
