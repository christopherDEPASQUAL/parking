<?php declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Payment;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(PaymentId $id): ?Payment;

    public function findLatestByReservationId(ReservationId $id): ?Payment;

    public function findLatestByAbonnementId(AbonnementId $id): ?Payment;

    public function findLatestByStationnementId(StationnementId $id): ?Payment;

    public function sumApprovedByParkingAndMonth(ParkingId $parkingId, int $year, int $month): int;
}
