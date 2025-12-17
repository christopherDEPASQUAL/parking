<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Port de persistance pour les réservations.
 */
interface ReservationRepositoryInterface
{
    public function save(Reservation $reservation): void;

    public function findById(ReservationId $id): ?Reservation;

    /**
     * Vérifie s'il existe une réservation active qui chevauche l'intervalle pour un parking donné.
     */
    public function hasOverlap(ParkingId $parkingId, DateRange $range): bool;

    /**
     * Vérifie si un utilisateur a déjà une réservation qui chevauche l'intervalle (optionnellement sur le même parking).
     */
    public function hasUserOverlap(UserId $userId, DateRange $range, ?ParkingId $parkingId = null): bool;

    /**
     * @return Reservation[]
     */
    public function listByParking(
        ParkingId $parkingId,
        ?ReservationStatus $status = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * @return Reservation[]
     */
    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array;

    /**
     * @return Reservation[]
     */
    public function listByUser(UserId $userId): array;

    public function countByParking(
        ParkingId $parkingId,
        ?ReservationStatus $status = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): int;
}
