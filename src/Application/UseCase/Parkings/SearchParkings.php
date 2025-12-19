<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\SearchParkingsRequest;
use App\Application\DTO\Parkings\SearchParkingsResponse;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\ParkingSearchQuery;
use DateTimeImmutable;

/**
 * Cas d'usage : Rechercher des parkings avec places disponibles autour d'une coordonnée GPS.
 */
final class SearchParkings
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function execute(SearchParkingsRequest $request): SearchParkingsResponse
    {
        $at = $request->at 
            ? new DateTimeImmutable($request->at) 
            : new DateTimeImmutable();

        $query = new ParkingSearchQuery(
            new GeoLocation($request->latitude, $request->longitude),
            $request->radiusKm ?? 5.0, // 5km par défaut
            $at,
            $request->minAvailableSpots ?? 1
        );

        $parkings = $this->parkingRepository->searchAvailable($query);

        $results = [];
        foreach ($parkings as $parking) {
            // Calculer la distance
            $distanceKm = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $parking->getLocation()->getLatitude(),
                $parking->getLocation()->getLongitude()
            );

            // Obtenir le contexte de disponibilité
            $context = $this->parkingRepository->getAvailabilityContext(
                $parking->getId(),
                $at
            );

            $availableSpots = $parking->freeSpotsAt(
                $at,
                $context['reservations'] ?? [],
                $context['abonnements'] ?? [],
                $context['stationnements'] ?? []
            );

            $results[] = [
                'id' => $parking->getId()->getValue(),
                'name' => $parking->getName(),
                'address' => $parking->getAddress(),
                'latitude' => $parking->getLocation()->getLatitude(),
                'longitude' => $parking->getLocation()->getLongitude(),
                'availableSpots' => $availableSpots,
                'totalCapacity' => $parking->getTotalCapacity(),
                'distanceKm' => round($distanceKm, 2),
            ];
        }

        // Trier par distance
        usort($results, fn($a, $b) => $a['distanceKm'] <=> $b['distanceKm']);

        return new SearchParkingsResponse($results);
    }

    /**
     * Calcule la distance en km entre deux points GPS (formule de Haversine).
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

