<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\GetParkingDetailsRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class GetParkingDetails
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(GetParkingDetailsRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $parking = $this->parkingRepository->findById($parkingId);

        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        return [
            'parking_id' => $parking->getId()->getValue(),
            'owner_id' => $parking->getUserId()->getValue(),
            'name' => $parking->getName(),
            'address' => $parking->getAddress(),
            'description' => $parking->getDescription(),
            'capacity' => $parking->getTotalCapacity(),
            'pricing_plan' => $parking->getPricingPlan()->toArray(),
            'location' => [
                'latitude' => $parking->getLocation()->getLatitude(),
                'longitude' => $parking->getLocation()->getLongitude(),
            ],
            'opening_schedule' => $parking->getOpeningSchedule()->toArray(),
            'created_at' => $parking->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $parking->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
