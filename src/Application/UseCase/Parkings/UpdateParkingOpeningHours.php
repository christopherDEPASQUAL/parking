<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\UpdateParkingOpeningHoursRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;

final class UpdateParkingOpeningHours
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    public function execute(UpdateParkingOpeningHoursRequest $request): void
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $parking = $this->parkingRepository->findById($parkingId);

        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        $schedule = $request->openingHours === []
            ? OpeningSchedule::alwaysOpen()
            : new OpeningSchedule($request->openingHours);

        $parking->changeOpeningSchedule($schedule);
        $this->parkingRepository->save($parking);
    }
}
