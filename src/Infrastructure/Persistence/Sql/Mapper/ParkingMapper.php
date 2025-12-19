<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Mapper;

use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Maps array payloads (from SQL rows or JSON) <-> Parking aggregate.
 *
 * The mapper keeps the infrastructure layer free from domain logic by delegating
 * instantiation details in one place.
 */
final class ParkingMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Parking $parking): array
    {
        return [
            'id' => $parking->getId()->getValue(),
            'owner_id' => $parking->getUserId()->getValue(),
            'name' => $parking->getName(),
            'address' => $parking->getAddress(),
            'description' => $parking->getDescription(),
            'capacity' => $parking->getTotalCapacity(),
            'pricing_plan' => $parking->getPricingPlan()->toArray(),
            'location' => [
                'lat' => $parking->getLocation()->getLatitude(),
                'lng' => $parking->getLocation()->getLongitude(),
            ],
            'opening_schedule' => $parking->getOpeningSchedule()->toArray(),
            'created_at' => $parking->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $parking->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): Parking
    {
        $createdAt = isset($data['created_at']) ? new DateTimeImmutable((string) $data['created_at']) : null;
        $updatedAt = isset($data['updated_at']) ? new DateTimeImmutable((string) $data['updated_at']) : null;

        return new Parking(
            ParkingId::fromString($data['id']),
            (string) $data['name'],
            (string) $data['address'],
            (int) $data['capacity'],
            PricingPlan::fromArray($data['pricing_plan'] ?? []),
            new GeoLocation(
                (float) ($data['location']['lat'] ?? 0.0),
                (float) ($data['location']['lng'] ?? 0.0)
            ),
            new OpeningSchedule($data['opening_schedule'] ?? []),
            UserId::fromString($data['owner_id']),
            $data['description'] ?? null,
            $createdAt,
            $updatedAt
        );
    }
}
