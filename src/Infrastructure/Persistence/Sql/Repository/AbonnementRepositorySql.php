<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\Abonnement;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
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
                'INSERT INTO subscriptions (id, user_id, parking_id, offer_id, starts_at, ends_at, status, created_at)
                 VALUES (:id, :user_id, :parking_id, :offer_id, :starts_at, :ends_at, :status, NOW())
                 ON DUPLICATE KEY UPDATE
                    user_id = VALUES(user_id),
                    parking_id = VALUES(parking_id),
                    offer_id = VALUES(offer_id),
                    starts_at = VALUES(starts_at),
                    ends_at = VALUES(ends_at),
                    status = VALUES(status)'
            );

            $stmt->execute([
                'id' => $abonnement->id()->getValue(),
                'user_id' => $abonnement->userId()->getValue(),
                'parking_id' => $abonnement->parkingId()->getValue(),
                'offer_id' => $abonnement->offerId()->getValue(),
                'starts_at' => $abonnement->startDate()->format('Y-m-d'),
                'ends_at' => $abonnement->endDate()->format('Y-m-d'),
                'status' => $this->mapStatusToSql($abonnement->status()),
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findById(AbonnementId $id): ?Abonnement
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM subscriptions WHERE id = :id');
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function listByParking(ParkingId $parkingId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM subscriptions WHERE parking_id = :parking_id';
        $params = ['parking_id' => $parkingId->getValue()];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $this->mapStatusToSql($status);
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    public function listByUser(UserId $userId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM subscriptions WHERE user_id = :user_id';
        $params = ['user_id' => $userId->getValue()];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $this->mapStatusToSql($status);
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        $result = [];
        foreach ($this->listByParking($parkingId, 'active') as $abonnement) {
            if ($abonnement->covers($at)) {
                $result[] = $abonnement;
            }
        }

        return $result;
    }

    private function hydrate(array $row): Abonnement
    {
        $slots = $this->fetchSlots((string) $row['offer_id']);

        return new Abonnement(
            AbonnementId::fromString((string) $row['id']),
            UserId::fromString((string) $row['user_id']),
            ParkingId::fromString((string) $row['parking_id']),
            SubscriptionOfferId::fromString((string) $row['offer_id']),
            $slots,
            new DateTimeImmutable((string) $row['starts_at']),
            new DateTimeImmutable((string) $row['ends_at']),
            $this->mapStatusFromSql((string) $row['status'])
        );
    }

    private function fetchSlots(string $offerId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT start_day_of_week, end_day_of_week, start_time, end_time
             FROM subscription_offer_slots WHERE offer_id = :id'
        );
        $stmt->execute(['id' => $offerId]);

        $slots = [];
        while ($row = $stmt->fetch()) {
            $slots[] = [
                'start_day' => (int) $row['start_day_of_week'],
                'end_day' => (int) $row['end_day_of_week'],
                'start_time' => substr((string) $row['start_time'], 0, 5),
                'end_time' => substr((string) $row['end_time'], 0, 5),
            ];
        }

        return $slots;
    }

    private function mapStatusToSql(string $status): string
    {
        return match ($status) {
            'suspended' => 'paused',
            default => $status,
        };
    }

    private function mapStatusFromSql(string $status): string
    {
        return match ($status) {
            'paused' => 'suspended',
            default => $status,
        };
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}
