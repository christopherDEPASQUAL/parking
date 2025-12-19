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

    public function listByUser(UserId $userId): array;

    public function listByParking(ParkingId $parkingId): array;

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array;
}

