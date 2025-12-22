<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\SubscriptionOffer;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use PDO;

final class SubscriptionOfferRepositorySql implements SubscriptionOfferRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory) {}

    public function save(SubscriptionOffer $offer): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO subscription_offers (id, parking_id, label, type, price_cents, status, created_at, updated_at)
                 VALUES (:id, :parking_id, :label, :type, :price_cents, :status, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    label = VALUES(label),
                    type = VALUES(type),
                    price_cents = VALUES(price_cents),
                    status = VALUES(status),
                    updated_at = VALUES(updated_at)'
            );

            $stmt->execute([
                'id' => $offer->id()->getValue(),
                'parking_id' => $offer->parkingId()->getValue(),
                'label' => $offer->label(),
                'type' => $offer->type(),
                'price_cents' => $offer->priceCents(),
                'status' => $offer->status(),
            ]);

            $pdo->prepare('DELETE FROM subscription_offer_slots WHERE offer_id = :id')
                ->execute(['id' => $offer->id()->getValue()]);

            $insertSlot = $pdo->prepare(
                'INSERT INTO subscription_offer_slots (offer_id, start_day_of_week, end_day_of_week, start_time, end_time)
                 VALUES (:offer_id, :start_day, :end_day, :start_time, :end_time)'
            );

            foreach ($offer->weeklyTimeSlots() as $slot) {
                $insertSlot->execute([
                    'offer_id' => $offer->id()->getValue(),
                    'start_day' => (int) $slot['start_day'],
                    'end_day' => (int) $slot['end_day'],
                    'start_time' => $this->normalizeTime($slot['start_time']),
                    'end_time' => $this->normalizeTime($slot['end_time']),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findById(SubscriptionOfferId $id): ?SubscriptionOffer
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM subscription_offers WHERE id = :id');
        $stmt->execute(['id' => $id->getValue()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function listByParking(ParkingId $parkingId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM subscription_offers WHERE parking_id = :parking_id';
        $params = ['parking_id' => $parkingId->getValue()];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    private function hydrate(array $row): SubscriptionOffer
    {
        $slots = $this->fetchSlots((string) $row['id']);

        return new SubscriptionOffer(
            SubscriptionOfferId::fromString((string) $row['id']),
            ParkingId::fromString((string) $row['parking_id']),
            (string) $row['label'],
            (string) $row['type'],
            (int) $row['price_cents'],
            $slots,
            (string) $row['status']
        );
    }

    /**
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
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

    private function normalizeTime(string $time): string
    {
        if ($time === '24:00') {
            return '23:59:59';
        }

        return sprintf('%s:00', $time);
    }

    private function pdo(): PDO
    {
        return $this->connectionFactory->create();
    }
}
