<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSearchQuery;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Port interface for Parking persistence and projections.
 *
 * L'implementations SQL restera interchangeables et
 * ne devra pas impacter les entites ni les use cases.
 */
interface ParkingRepositoryInterface
{
    public function save(Parking $parking): void;

    public function delete(ParkingId $parkingId): void;

    public function findById(ParkingId $parkingId): ?Parking;

    /**
     * @return Parking[]
     */
    public function findByOwnerId(UserId $ownerId): array;

    /**
     * Variante extensible basée sur un objet requête.
     *
     * @return Parking[]
     */
    public function searchAvailable(ParkingSearchQuery $query): array;

    /**
     * Nombre de places disponibles pour un parking a un instant donne (>= 0).
     */
    public function getAvailabilityAt(ParkingId $parkingId, DateTimeImmutable $at): int;

    /**
     * Donne le contexte nécessaire au calcul métier de disponibilité (réservations, abonnements, stationnements).
     *
     * @return array{reservations:iterable, abonnements:iterable, stationnements:iterable}
     */
    public function getAvailabilityContext(ParkingId $parkingId, DateTimeImmutable $at): array;

    /**
     * Chiffre d'affaire mensuel (centimes) calcule a partir des reservations terminees et des abonnements.
     */
    public function getMonthlyRevenueCents(ParkingId $parkingId, int $year, int $month): int;
}
