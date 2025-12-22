<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\Payment;
use App\Domain\Enum\PaymentStatus;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use PDO;

final class PaymentRepositorySql implements PaymentRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory) {}

    public function save(Payment $payment): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO payments (id, user_id, reservation_id, subscription_id, stationing_id, status, amount, provider_reference, created_at)
             VALUES (:id, :user_id, :reservation_id, :subscription_id, :stationing_id, :status, :amount, :provider_reference, :created_at)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                reservation_id = VALUES(reservation_id),
                subscription_id = VALUES(subscription_id),
                stationing_id = VALUES(stationing_id),
                status = VALUES(status),
                amount = VALUES(amount),
                provider_reference = VALUES(provider_reference)'
        );

        $stmt->execute([
            'id' => $payment->id()->getValue(),
            'user_id' => $payment->userId()->getValue(),
            'reservation_id' => $payment->reservationId()?->getValue(),
            'subscription_id' => $payment->abonnementId()?->getValue(),
            'stationing_id' => $payment->stationnementId()?->getValue(),
            'status' => $payment->status()->value,
            'amount' => number_format($payment->amount()->toFloat(), 2, '.', ''),
            'provider_reference' => $payment->providerReference(),
            'created_at' => $payment->createdAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function findById(PaymentId $id): ?Payment
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM payments WHERE id = :id');
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function findLatestByReservationId(ReservationId $id): ?Payment
    {
        return $this->findLatestBy('reservation_id', $id->getValue());
    }

    public function findLatestByAbonnementId(AbonnementId $id): ?Payment
    {
        return $this->findLatestBy('subscription_id', $id->getValue());
    }

    public function findLatestByStationnementId(StationnementId $id): ?Payment
    {
        return $this->findLatestBy('stationing_id', $id->getValue());
    }

    public function sumApprovedByParkingAndMonth(ParkingId $parkingId, int $year, int $month): int
    {
        $stmt = $this->pdo()->prepare(
            'SELECT SUM(p.amount) AS total
             FROM payments p
             LEFT JOIN reservations r ON p.reservation_id = r.id
             LEFT JOIN subscriptions s ON p.subscription_id = s.id
             LEFT JOIN stationings st ON p.stationing_id = st.id
             WHERE p.status = :status
               AND YEAR(p.created_at) = :year
               AND MONTH(p.created_at) = :month
               AND (
                    r.parking_id = :parking_id
                 OR s.parking_id = :parking_id
                 OR st.parking_id = :parking_id
               )'
        );

        $stmt->execute([
            'status' => PaymentStatus::APPROVED->value,
            'year' => $year,
            'month' => $month,
            'parking_id' => $parkingId->getValue(),
        ]);

        $total = $stmt->fetchColumn();
        if ($total === false || $total === null) {
            return 0;
        }

        return (int) round(((float) $total) * 100);
    }

    private function findLatestBy(string $field, string $value): ?Payment
    {
        $stmt = $this->pdo()->prepare(
            "SELECT * FROM payments WHERE {$field} = :value ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    private function hydrate(array $row): Payment
    {
        return new Payment(
            PaymentId::fromString((string) $row['id']),
            UserId::fromString((string) $row['user_id']),
            Money::fromFloat((float) $row['amount'], 'EUR'),
            PaymentStatus::from((string) $row['status']),
            !empty($row['reservation_id']) ? ReservationId::fromString((string) $row['reservation_id']) : null,
            !empty($row['subscription_id']) ? AbonnementId::fromString((string) $row['subscription_id']) : null,
            !empty($row['stationing_id']) ? StationnementId::fromString((string) $row['stationing_id']) : null,
            $row['provider_reference'] ?? null,
            isset($row['created_at']) ? new \DateTimeImmutable((string) $row['created_at']) : null
        );
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}
