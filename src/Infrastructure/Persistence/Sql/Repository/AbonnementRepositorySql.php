<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\Abonnement;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use DateTimeImmutable;
use PDO;

final class AbonnementRepositorySql implements AbonnementRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory) {}

    public function save(Abonnement $abonnement): void
    {
        $pdo = $this->pdo();

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO subscriptions (id, user_id, parking_id, starts_at, ends_at, status, created_at)
                 VALUES (:id, :user_id, :parking_id, :starts_at, :ends_at, :status, :created_at)
                 ON DUPLICATE KEY UPDATE
                    user_id = VALUES(user_id),
                    parking_id = VALUES(parking_id),
                    starts_at = VALUES(starts_at),
                    ends_at = VALUES(ends_at),
                    status = VALUES(status)'
            );

            $stmt->execute([
                'id' => $abonnement->id()->getValue(),
                'user_id' => $abonnement->userId()->getValue(),
                'parking_id' => $abonnement->parkingId()->getValue(),
                'starts_at' => $abonnement->startDate()->format('Y-m-d'),
                'ends_at' => $abonnement->endDate()->format('Y-m-d'),
                'status' => $abonnement->status(),
                'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);

            $stmt = $pdo->prepare('DELETE FROM subscription_slots WHERE subscription_id = :id');
            $stmt->execute(['id' => $abonnement->id()->getValue()]);

            $stmt = $pdo->prepare(
                'INSERT INTO subscription_slots (subscription_id, day_of_week, start_time, end_time)
                 VALUES (:subscription_id, :day_of_week, :start_time, :end_time)'
            );

            foreach ($abonnement->weeklyTimeSlots() as $slot) {
                $stmt->execute([
                    'subscription_id' => $abonnement->id()->getValue(),
                    'day_of_week' => $slot['day'] ?? $slot['dayOfWeek'] ?? 0,
                    'start_time' => $slot['start'] ?? $slot['startTime'] ?? '00:00:00',
                    'end_time' => $slot['end'] ?? $slot['endTime'] ?? '23:59:59'
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function findById(AbonnementId $id): ?Abonnement
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'SELECT s.*, GROUP_CONCAT(CONCAT(ss.day_of_week, ":", ss.start_time, "-", ss.end_time) SEPARATOR ",") as slots
             FROM subscriptions s
             LEFT JOIN subscription_slots ss ON s.id = ss.subscription_id
             WHERE s.id = :id
             GROUP BY s.id'
        );
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->fromRow($row);
    }

    public function listByUser(UserId $userId): array
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'SELECT s.*, GROUP_CONCAT(CONCAT(ss.day_of_week, ":", ss.start_time, "-", ss.end_time) SEPARATOR ",") as slots
             FROM subscriptions s
             LEFT JOIN subscription_slots ss ON s.id = ss.subscription_id
             WHERE s.user_id = :user_id
             GROUP BY s.id'
        );
        $stmt->execute(['user_id' => $userId->getValue()]);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->fromRow($row);
        }

        return $result;
    }

    public function listByParking(ParkingId $parkingId): array
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'SELECT s.*, GROUP_CONCAT(CONCAT(ss.day_of_week, ":", ss.start_time, "-", ss.end_time) SEPARATOR ",") as slots
             FROM subscriptions s
             LEFT JOIN subscription_slots ss ON s.id = ss.subscription_id
             WHERE s.parking_id = :parking_id
             GROUP BY s.id'
        );
        $stmt->execute(['parking_id' => $parkingId->getValue()]);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->fromRow($row);
        }

        return $result;
    }

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'SELECT s.*, GROUP_CONCAT(CONCAT(ss.day_of_week, ":", ss.start_time, "-", ss.end_time) SEPARATOR ",") as slots
             FROM subscriptions s
             LEFT JOIN subscription_slots ss ON s.id = ss.subscription_id
             WHERE s.parking_id = :parking_id
               AND s.status = "active"
               AND s.starts_at <= :at
               AND s.ends_at >= :at
             GROUP BY s.id'
        );
        $stmt->execute([
            'parking_id' => $parkingId->getValue(),
            'at' => $at->format('Y-m-d')
        ]);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $abonnement = $this->fromRow($row);
            if ($abonnement->coversTimeSlot($at)) {
                $result[] = $abonnement;
            }
        }

        return $result;
    }

    private function fromRow(array $row): Abonnement
    {
        $slots = [];
        if (!empty($row['slots'])) {
            foreach (explode(',', $row['slots']) as $slotStr) {
                $parts = explode(':', $slotStr);
                $timeParts = explode('-', $parts[1] ?? '00:00:00-23:59:59');
                $slots[] = [
                    'day' => (int) $parts[0],
                    'start' => $timeParts[0] ?? '00:00:00',
                    'end' => $timeParts[1] ?? '23:59:59'
                ];
            }
        }

        return new Abonnement(
            AbonnementId::fromString($row['id']),
            UserId::fromString($row['user_id']),
            ParkingId::fromString($row['parking_id']),
            $slots,
            new DateTimeImmutable($row['starts_at']),
            new DateTimeImmutable($row['ends_at']),
            $row['status']
        );
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}

