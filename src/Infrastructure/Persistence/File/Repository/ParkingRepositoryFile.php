<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSearchQuery;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Mapper\ParkingMapper;
use DateTimeImmutable;

/**
 * Simple JSON-based implementation of the ParkingRepository port.
 *
 * Notes:
 *  - This adapter is meant for local/demo usage; it keeps a flat JSON file in storage.
 *  - It relies on the shared ParkingMapper to keep conversion logic centralised.
 */
final class ParkingRepositoryFile implements ParkingRepositoryInterface
{
    private ParkingMapper $mapper;
    private string $filePath;

    public function __construct(?string $filePath = null, ?ParkingMapper $mapper = null)
    {
        $resolved = $filePath ?? (getenv('JSON_PARKING_STORAGE') ?: 'storage/parkings.json');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 5) . '/' . ltrim($resolved, '/\\');
        }
        $this->filePath = $resolved;
        $this->mapper = $mapper ?? new ParkingMapper();
    }

    public function save(Parking $parking): void
    {
        $data = $this->load();
        $data[$parking->getId()->getValue()] = $this->mapper->toArray($parking);
        $this->persist($data);
    }

    public function delete(ParkingId $parkingId): void
    {
        $data = $this->load();
        unset($data[$parkingId->getValue()]);
        $this->persist($data);
    }

    public function findById(ParkingId $parkingId): ?Parking
    {
        $data = $this->load();
        if (!isset($data[$parkingId->getValue()])) {
            return null;
        }

        return $this->mapper->fromArray($data[$parkingId->getValue()]);
    }

    public function findByOwnerId(UserId $ownerId): array
    {
        $result = [];
        foreach ($this->load() as $parking) {
            if (($parking['owner_id'] ?? null) === $ownerId->getValue()) {
                $result[] = $this->mapper->fromArray($parking);
            }
        }

        return $result;
    }

    public function searchAvailable(ParkingSearchQuery $query): array
    {
        $result = [];
        foreach ($this->load() as $raw) {
            $parking = $this->mapper->fromArray($raw);

            // Filtre : propriÇ¸taire
            if ($query->ownerId() !== null && !$parking->getUserId()->equals($query->ownerId())) {
                continue;
            }

            // Filtre : gÇ¸oloc + rayon
            if (!$parking->getLocation()->isWithinRadius($query->center(), $query->radiusKm())) {
                continue;
            }

            // Filtre : ouverture
            if (!$parking->isOpenAt($query->at())) {
                continue;
            }

            // Filtre : prix max (approximatif sur 60 minutes)
            if ($query->maxPriceCents() !== null) {
                $priceForHour = $parking->computePriceForDurationMinutes(60);
                if ($priceForHour > $query->maxPriceCents()) {
                    continue;
                }
            }

            // Filtre : disponibilitÇ¸ minimale (on n'a pas de contexte rÇ¸servations ici)
            if ($parking->getTotalCapacity() < $query->minimumFreeSpots()) {
                continue;
            }

            $result[] = $parking;
        }

        return $result;
    }

    public function getAvailabilityAt(ParkingId $parkingId, DateTimeImmutable $at): int
    {
        $parking = $this->findById($parkingId);
        if ($parking === null || !$parking->isOpenAt($at)) {
            return 0;
        }

        // Sans contexte rÇ¸servation/stationnement, on considère toute la capacité disponible.
        return $parking->getTotalCapacity();
    }

    public function getAvailabilityContext(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        // Pas de stockage des rÇ¸servations/abonnements/stationnements dans cet adaptateur.
        return [
            'reservations' => [],
            'abonnements' => [],
            'stationnements' => [],
        ];
    }

    public function getMonthlyRevenueCents(ParkingId $parkingId, int $year, int $month): int
    {
        // Pas de flux financier dans le stockage fichier.
        return 0;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function load(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
