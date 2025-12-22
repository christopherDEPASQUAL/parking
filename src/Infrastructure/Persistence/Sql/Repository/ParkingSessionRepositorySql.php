<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use DateTimeImmutable;
use PDO;

final class ParkingSessionRepositorySql implements ParkingSessionRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory) {}

    public function save(ParkingSession $session): void
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'INSERT INTO stationings (id, parking_id, user_id, entered_at, exited_at)
             VALUES (:id, :parking_id, :user_id, :entered_at, :exited_at)
             ON DUPLICATE KEY UPDATE
                parking_id = VALUES(parking_id),
                user_id = VALUES(user_id),
                entered_at = VALUES(entered_at),
                exited_at = VALUES(exited_at)'
        );

        $stmt->execute([
            'id' => $session->getId()->getValue(),
            'parking_id' => $session->getParkingId()->getValue(),
            'user_id' => $session->getUserId()->getValue(),
            'entered_at' => $session->getStartedAt()->format('Y-m-d H:i:s'),
            'exited_at' => $session->getEndedAt()?->format('Y-m-d H:i:s')
        ]);
    }

    public function findById(StationnementId $id): ?ParkingSession
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare('SELECT * FROM stationings WHERE id = :id');
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->fromRow($row);
    }

    public function findActiveByUserAndParking(UserId $userId, ParkingId $parkingId): ?ParkingSession
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'SELECT * FROM stationings
             WHERE user_id = :user_id
               AND parking_id = :parking_id
               AND exited_at IS NULL
             ORDER BY entered_at DESC
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId->getValue(),
            'parking_id' => $parkingId->getValue()
        ]);
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
            'SELECT * FROM stationings
             WHERE user_id = :user_id
             ORDER BY entered_at DESC'
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
            'SELECT * FROM stationings
             WHERE parking_id = :parking_id
             ORDER BY entered_at DESC'
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
            'SELECT * FROM stationings
             WHERE parking_id = :parking_id
               AND entered_at <= :at
               AND (exited_at IS NULL OR exited_at >= :at)'
        );
        $stmt->execute([
            'parking_id' => $parkingId->getValue(),
            'at' => $at->format('Y-m-d H:i:s')
        ]);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->fromRow($row);
        }

        return $result;
    }

    private function fromRow(array $row): ParkingSession
    {
        $spotId = ParkingSpotId::generate();

        $session = ParkingSession::start(
            ParkingId::fromString($row['parking_id']),
            UserId::fromString($row['user_id']),
            $spotId,
            new DateTimeImmutable($row['entered_at'])
        );

        if (!empty($row['exited_at'])) {
            $session->close(new DateTimeImmutable($row['exited_at']));
        }

        return $session;
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}

