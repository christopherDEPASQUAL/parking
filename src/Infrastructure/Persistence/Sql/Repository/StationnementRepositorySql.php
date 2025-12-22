<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use DateTimeImmutable;
use PDO;

final class StationnementRepositorySql implements StationnementRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory) {}

    public function save(ParkingSession $session): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO stationings (id, parking_id, user_id, reservation_id, subscription_id, entered_at, exited_at, amount)
             VALUES (:id, :parking_id, :user_id, :reservation_id, :subscription_id, :entered_at, :exited_at, :amount)
             ON DUPLICATE KEY UPDATE
                parking_id = VALUES(parking_id),
                user_id = VALUES(user_id),
                reservation_id = VALUES(reservation_id),
                subscription_id = VALUES(subscription_id),
                entered_at = VALUES(entered_at),
                exited_at = VALUES(exited_at),
                amount = VALUES(amount)'
        );

        $amount = $session->getAmount();
        $stmt->execute([
            'id' => $session->getId()->getValue(),
            'parking_id' => $session->getParkingId()->getValue(),
            'user_id' => $session->getUserId()->getValue(),
            'reservation_id' => $session->getReservationId()?->getValue(),
            'subscription_id' => $session->getAbonnementId()?->getValue(),
            'entered_at' => $session->getStartedAt()->format('Y-m-d H:i:s'),
            'exited_at' => $session->getEndedAt()?->format('Y-m-d H:i:s'),
            'amount' => $amount ? number_format($amount->toFloat(), 2, '.', '') : null,
        ]);
    }

    public function findById(StationnementId $id): ?ParkingSession
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM stationings WHERE id = :id');
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findActiveByUser(UserId $userId, ParkingId $parkingId): ?ParkingSession
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM stationings WHERE user_id = :user_id AND parking_id = :parking_id AND exited_at IS NULL'
        );
        $stmt->execute([
            'user_id' => $userId->getValue(),
            'parking_id' => $parkingId->getValue(),
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function listByParking(
        ParkingId $parkingId,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $sql = 'SELECT * FROM stationings WHERE parking_id = :parking_id';
        $params = ['parking_id' => $parkingId->getValue()];

        if ($from !== null) {
            $sql .= ' AND (exited_at IS NULL OR exited_at >= :from)';
            $params['from'] = $from->format('Y-m-d H:i:s');
        }
        if ($to !== null) {
            $sql .= ' AND entered_at <= :to';
            $params['to'] = $to->format('Y-m-d H:i:s');
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
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM stationings
             WHERE parking_id = :parking_id
               AND entered_at <= :at
               AND (exited_at IS NULL OR exited_at >= :at)'
        );
        $stmt->execute([
            'parking_id' => $parkingId->getValue(),
            'at' => $at->format('Y-m-d H:i:s'),
        ]);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    public function listByUser(UserId $userId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM stationings WHERE user_id = :user_id ORDER BY entered_at DESC');
        $stmt->execute(['user_id' => $userId->getValue()]);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    private function hydrate(array $row): ParkingSession
    {
        $reservationId = !empty($row['reservation_id'])
            ? ReservationId::fromString((string) $row['reservation_id'])
            : null;
        $abonnementId = !empty($row['subscription_id'])
            ? AbonnementId::fromString((string) $row['subscription_id'])
            : null;

        $amount = null;
        if (isset($row['amount']) && $row['amount'] !== null) {
            $amount = Money::fromFloat((float) $row['amount'], 'EUR');
        }

        return ParkingSession::fromPersistence(
            StationnementId::fromString((string) $row['id']),
            ParkingId::fromString((string) $row['parking_id']),
            UserId::fromString((string) $row['user_id']),
            $reservationId,
            $abonnementId,
            new DateTimeImmutable((string) $row['entered_at']),
            isset($row['exited_at']) && $row['exited_at'] !== null
                ? new DateTimeImmutable((string) $row['exited_at'])
                : null,
            $amount
        );
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}
