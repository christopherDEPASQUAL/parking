<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

interface StationnementRepositoryInterface
{
    public function save(ParkingSession $session): void;

    public function findById(StationnementId $id): ?ParkingSession;

    public function findActiveByUser(UserId $userId, ParkingId $parkingId): ?ParkingSession;

    /**
     * @return ParkingSession[]
     */
    public function listByParking(
        ParkingId $parkingId,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array;

    /**
     * @return ParkingSession[]
     */
    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array;

    /**
     * @return ParkingSession[]
     */
    public function listByUser(UserId $userId): array;
}
