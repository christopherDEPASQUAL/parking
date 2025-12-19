<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Port de persistance pour les stationnements (ParkingSession).
 */
interface ParkingSessionRepositoryInterface
{
    public function save(ParkingSession $session): void;

    public function findById(StationnementId $id): ?ParkingSession;

    /**
     * Trouve le stationnement actif d'un utilisateur dans un parking.
     */
    public function findActiveByUserAndParking(UserId $userId, ParkingId $parkingId): ?ParkingSession;

    /**
     * @return ParkingSession[]
     */
    public function listByUser(UserId $userId): array;

    /**
     * @return ParkingSession[]
     */
    public function listByParking(ParkingId $parkingId): array;

    /**
     * @return ParkingSession[]
     */
    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array;
}

