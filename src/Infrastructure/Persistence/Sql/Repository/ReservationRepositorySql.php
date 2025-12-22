<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use DateTimeImmutable;
use PDO;

/**
 * Implémentation SQL du port ReservationRepository.
 */
final class ReservationRepositorySql implements ReservationRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory) {}

    public function save(Reservation $reservation): void
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'INSERT INTO reservations (id, user_id, parking_id, starts_at, ends_at, status, price, currency, created_at, cancelled_at, cancellation_reason)
             VALUES (:id, :user_id, :parking_id, :starts_at, :ends_at, :status, :price, :currency, :created_at, :cancelled_at, :cancellation_reason)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                parking_id = VALUES(parking_id),
                starts_at = VALUES(starts_at),
                ends_at = VALUES(ends_at),
                status = VALUES(status),
                price = VALUES(price),
                currency = VALUES(currency),
                cancelled_at = VALUES(cancelled_at),
                cancellation_reason = VALUES(cancellation_reason)'
        );

        $stmt->execute([
            'id' => $reservation->id()->getValue(),
            'user_id' => $reservation->userId()->getValue(),
            'parking_id' => $reservation->parkingId()->getValue(),
            'starts_at' => $reservation->dateRange()->getStart()->format('Y-m-d H:i:s'),
            'ends_at' => $reservation->dateRange()->getEnd()->format('Y-m-d H:i:s'),
            'status' => strtolower($reservation->status()->value),
            'price' => $this->moneyToSql($reservation->price()),
            'currency' => $reservation->price()->getCurrency(),
            'created_at' => $reservation->createdAt()->format('Y-m-d H:i:s'),
            'cancelled_at' => $reservation->cancelledAt()?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $reservation->cancellationReason(),
        ]);
    }

    public function findById(ReservationId $id): ?Reservation
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM reservations WHERE id = :id');
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function hasOverlap(ParkingId $parkingId, DateRange $range): bool
    {
        $sql = "SELECT COUNT(*) FROM reservations
                WHERE parking_id = :parking_id
                  AND status IN ('pending_payment','pending','confirmed')
                  AND starts_at < :range_end AND ends_at > :range_start";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute([
            'parking_id' => $parkingId->getValue(),
            'range_end' => $range->getEnd()->format('Y-m-d H:i:s'),
            'range_start' => $range->getStart()->format('Y-m-d H:i:s'),
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasUserOverlap(UserId $userId, DateRange $range, ?ParkingId $parkingId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM reservations
                WHERE user_id = :user_id
                  AND status IN ('pending_payment','pending','confirmed')
                  AND starts_at < :range_end AND ends_at > :range_start";

        $params = [
            'user_id' => $userId->getValue(),
            'range_end' => $range->getEnd()->format('Y-m-d H:i:s'),
            'range_start' => $range->getStart()->format('Y-m-d H:i:s'),
        ];

        if ($parkingId !== null) {
            $sql .= " AND parking_id = :parking_id";
            $params['parking_id'] = $parkingId->getValue();
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function listByParking(
        ParkingId $parkingId,
        ?ReservationStatus $status = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM reservations WHERE parking_id = :parking_id";
        $params = ['parking_id' => $parkingId->getValue()];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = strtolower($status->value);
        }
        if ($from !== null) {
            $sql .= " AND ends_at >= :from";
            $params['from'] = $from->format('Y-m-d H:i:s');
        }
        if ($to !== null) {
            $sql .= " AND starts_at <= :to";
            $params['to'] = $to->format('Y-m-d H:i:s');
        }

        $sql .= " ORDER BY starts_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $reservations = [];
        while ($row = $stmt->fetch()) {
            $reservations[] = $this->hydrate($row);
        }

        return $reservations;
    }

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        $sql = "SELECT * FROM reservations
                WHERE parking_id = :parking_id
                  AND status IN ('pending_payment','pending','confirmed')
                  AND starts_at <= :at AND ends_at >= :at";

        $stmt = $this->pdo()->prepare($sql);
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
        $stmt = $this->pdo()->prepare("SELECT * FROM reservations WHERE user_id = :user_id ORDER BY starts_at DESC");
        $stmt->execute(['user_id' => $userId->getValue()]);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    public function countByParking(
        ParkingId $parkingId,
        ?ReservationStatus $status = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): int {
        $sql = "SELECT COUNT(*) FROM reservations WHERE parking_id = :parking_id";
        $params = ['parking_id' => $parkingId->getValue()];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = strtolower($status->value);
        }
        if ($from !== null) {
            $sql .= " AND ends_at >= :from";
            $params['from'] = $from->format('Y-m-d H:i:s');
        }
        if ($to !== null) {
            $sql .= " AND starts_at <= :to";
            $params['to'] = $to->format('Y-m-d H:i:s');
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): Reservation
    {
        $range = DateRange::fromDateTimes(
            new DateTimeImmutable((string) $row['starts_at']),
            new DateTimeImmutable((string) $row['ends_at'])
        );

        $status = ReservationStatus::from(strtoupper((string) $row['status']));
        $currency = isset($row['currency']) ? (string) $row['currency'] : 'EUR';
        $price = isset($row['price']) ? Money::fromFloat((float) $row['price'], $currency) : Money::fromCents(0, $currency);

        $reservation = new Reservation(
            ReservationId::fromString((string) $row['id']),
            UserId::fromString((string) $row['user_id']),
            ParkingId::fromString((string) $row['parking_id']),
            $range,
            $price,
            $status,
            isset($row['created_at']) ? new DateTimeImmutable((string) $row['created_at']) : null
        );

        if (!empty($row['cancelled_at'])) {
            $ref = new \ReflectionClass($reservation);
            if ($ref->hasProperty('cancelledAt')) {
                $prop = $ref->getProperty('cancelledAt');
                $prop->setAccessible(true);
                $prop->setValue($reservation, new DateTimeImmutable((string) $row['cancelled_at']));
            }
            if ($ref->hasProperty('cancellationReason')) {
                $prop = $ref->getProperty('cancellationReason');
                $prop->setAccessible(true);
                $prop->setValue($reservation, $row['cancellation_reason'] ?? null);
            }
            if ($ref->hasProperty('status')) {
                $prop = $ref->getProperty('status');
                $prop->setAccessible(true);
                $prop->setValue($reservation, ReservationStatus::CANCELLED);
            }
        }

        return $reservation;
    }

    private function moneyToSql(Money $money): string
    {
        return number_format($money->toFloat(), 2, '.', '');
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}
