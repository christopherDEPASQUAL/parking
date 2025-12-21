<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\CreateParkingRequest;
use App\Application\DTO\Parkings\CreateParkingResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\UserId;

/**
 * Cas d'usage : creation d'un parking (cote application).
 */
final class CreateParking
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    public function execute(CreateParkingRequest $request): CreateParkingResponse
    {
        $pricingPlan = new PricingPlan(
            $request->pricingTiers,
            $request->defaultPricePerStepCents,
            $request->overstayPenaltyCents ?? 2000,
            $request->subscriptionPrices
        );

        $opening = $request->openingHours === []
            ? OpeningSchedule::alwaysOpen()
            : new OpeningSchedule($request->openingHours);

        $parking = new Parking(
            ParkingId::generate(),
            $request->name,
            $request->address,
            $request->capacity,
            $pricingPlan,
            new GeoLocation($request->latitude, $request->longitude),
            $opening,
            UserId::fromString($request->ownerId),
            $request->description
        );

        $this->parkingRepository->save($parking);

        return new CreateParkingResponse(
            $parking->getId()->getValue(),
            $parking->getTotalCapacity(),
            $parking->getCreatedAt()
        );
    }
}
