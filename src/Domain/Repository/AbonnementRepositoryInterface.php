<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Abonnement;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

interface AbonnementRepositoryInterface
{
    public function save(Abonnement $abonnement): void;

    public function findById(AbonnementId $id): ?Abonnement;

    /**
     * @return Abonnement[]
     */
    public function listByParking(ParkingId $parkingId, ?string $status = null): array;

    /**
     * @return Abonnement[]
     */
    public function listByUser(UserId $userId, ?string $status = null): array;

    /**
     * @return Abonnement[]
     */
    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array;
}
