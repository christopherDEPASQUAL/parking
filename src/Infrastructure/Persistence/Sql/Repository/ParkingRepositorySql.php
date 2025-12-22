<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Domain\Entity\Parking;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSearchQuery;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use App\Infrastructure\Persistence\Sql\Mapper\ParkingMapper;
use DateTimeImmutable;
use PDO;

final class ParkingRepositorySql implements ParkingRepositoryInterface
{
    private ParkingMapper $mapper;
    private ReservationRepositoryInterface $reservationRepository;
    private AbonnementRepositoryInterface $abonnementRepository;
    private StationnementRepositoryInterface $stationnementRepository;
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(
        private readonly PdoConnectionFactory $connectionFactory,
        ?ParkingMapper $mapper = null,
        ?ReservationRepositoryInterface $reservationRepository = null,
        ?AbonnementRepositoryInterface $abonnementRepository = null,
        ?StationnementRepositoryInterface $stationnementRepository = null,
        ?PaymentRepositoryInterface $paymentRepository = null
    ) {
        $this->mapper = $mapper ?? new ParkingMapper();
        $this->reservationRepository = $reservationRepository ?? new ReservationRepositorySql($connectionFactory);
        $this->abonnementRepository = $abonnementRepository ?? new AbonnementRepositorySql($connectionFactory);
        $this->stationnementRepository = $stationnementRepository ?? new StationnementRepositorySql($connectionFactory);
        $this->paymentRepository = $paymentRepository ?? new PaymentRepositorySql($connectionFactory);
    }

    public function save(Parking $parking): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO parkings (id, owner_id, name, address, description, latitude, longitude, capacity, created_at, updated_at)
                 VALUES (:id, :owner_id, :name, :address, :description, :lat, :lng, :capacity, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    address = VALUES(address),
                    description = VALUES(description),
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude),
                    capacity = VALUES(capacity),
                    updated_at = VALUES(updated_at)'
            );

            $stmt->execute([
                'id' => $parking->getId()->getValue(),
                'owner_id' => $parking->getUserId()->getValue(),
                'name' => $parking->getName(),
                'address' => $parking->getAddress(),
                'description' => $parking->getDescription(),
                'lat' => $parking->getLocation()->getLatitude(),
                'lng' => $parking->getLocation()->getLongitude(),
                'capacity' => $parking->getTotalCapacity(),
                'created_at' => $parking->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $parking->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);

            $this->upsertPricingPlan($pdo, $parking);
            $this->replaceOpeningHours($pdo, $parking);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function delete(ParkingId $parkingId): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('DELETE FROM parking_pricing_plans WHERE parking_id = :id')->execute(['id' => $parkingId->getValue()]);
            $pdo->prepare('DELETE FROM opening_hours WHERE parking_id = :id')->execute(['id' => $parkingId->getValue()]);
            $pdo->prepare('DELETE FROM parkings WHERE id = :id')->execute(['id' => $parkingId->getValue()]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findById(ParkingId $parkingId): ?Parking
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT * FROM parkings WHERE id = :id');
        $stmt->execute(['id' => $parkingId->getValue()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrateParking($row);
    }

    public function findByOwnerId(UserId $ownerId): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT * FROM parkings WHERE owner_id = :owner_id');
        $stmt->execute(['owner_id' => $ownerId->getValue()]);

        $parkings = [];
        while ($row = $stmt->fetch()) {
            $parkings[] = $this->hydrateParking($row);
        }

        return $parkings;
    }

    public function searchAvailable(ParkingSearchQuery $query): array
    {
        $pdo = $this->pdo();
        $nameFilter = $query->name();
        $rangeStart = $query->at();
        $rangeEnd = $query->endsAt();

        $params = [];
        $sql = 'SELECT * FROM parkings';
        if ($query->ownerId() !== null) {
            $sql .= ' WHERE owner_id = :owner_id';
            $params['owner_id'] = $query->ownerId()->getValue();
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch()) {
            $parking = $this->hydrateParking($row);

            if (!$parking->getLocation()->isWithinRadius($query->center(), $query->radiusKm())) {
                continue;
            }

            if (!$parking->isOpenAt($rangeStart) || ($rangeEnd !== null && !$parking->isOpenAt($rangeEnd))) {
                continue;
            }

            if ($nameFilter !== null && stripos($parking->getName(), $nameFilter) === false) {
                continue;
            }

            if ($query->maxPriceCents() !== null) {
                $priceForHour = $parking->computePriceForDurationMinutes(60);
                if ($priceForHour > $query->maxPriceCents()) {
                    continue;
                }
            }

            $context = $this->getAvailabilityContext($parking->getId(), $rangeStart);
            $free = $parking->freeSpotsAt(
                $rangeStart,
                $context['reservations'],
                $context['abonnements'],
                $context['stationnements']
            );
            if ($rangeEnd !== null && $rangeEnd > $rangeStart) {
                $contextEnd = $this->getAvailabilityContext($parking->getId(), $rangeEnd);
                $freeEnd = $parking->freeSpotsAt(
                    $rangeEnd,
                    $contextEnd['reservations'],
                    $contextEnd['abonnements'],
                    $contextEnd['stationnements']
                );
                $free = min($free, $freeEnd);
            }
            if ($free < $query->minimumFreeSpots()) {
                continue;
            }

            $result[] = $parking;
        }

        return $result;
    }

    public function getAvailabilityAt(ParkingId $parkingId, DateTimeImmutable $at): int
    {
        $parking = $this->findById($parkingId);
        if ($parking === null || !$parking->isOpenAt($at)) {
            return 0;
        }

        $context = $this->getAvailabilityContext($parkingId, $at);

        return $parking->freeSpotsAt($at, $context['reservations'], $context['abonnements'], $context['stationnements']);
    }

    public function getAvailabilityContext(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        return [
            'reservations' => $this->reservationRepository->listActiveAt($parkingId, $at),
            'abonnements' => $this->abonnementRepository->listActiveAt($parkingId, $at),
            'stationnements' => $this->stationnementRepository->listActiveAt($parkingId, $at),
        ];
    }

    public function getMonthlyRevenueCents(ParkingId $parkingId, int $year, int $month): int
    {
        return $this->paymentRepository->sumApprovedByParkingAndMonth($parkingId, $year, $month);
    }

    private function hydrateParking(array $row): Parking
    {
        $plan = $this->fetchPricingPlan($row['id']);
        $opening = $this->fetchOpeningSchedule($row['id']);

        $payload = [
            'id' => $row['id'],
            'owner_id' => $row['owner_id'],
            'name' => $row['name'],
            'address' => $row['address'] ?? '',
            'description' => $row['description'] ?? null,
            'capacity' => (int) $row['capacity'],
            'pricing_plan' => $plan,
            'location' => [
                'lat' => (float) $row['latitude'],
                'lng' => (float) $row['longitude'],
            ],
            'opening_schedule' => $opening,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];

        return $this->mapper->fromArray($payload);
    }

    private function fetchPricingPlan(string $parkingId): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT plan_json FROM parking_pricing_plans WHERE parking_id = :id');
        $stmt->execute(['id' => $parkingId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return [
                'tiers' => [],
                'defaultPricePerStepCents' => 0,
                'overstayPenaltyCents' => 2000,
                'stepMinutes' => 15,
            ];
        }

        $decoded = json_decode((string) $row['plan_json'], true);

        return is_array($decoded) ? $decoded : [
            'tiers' => [],
            'defaultPricePerStepCents' => 0,
            'overstayPenaltyCents' => 2000,
            'stepMinutes' => 15,
        ];
    }

    private function fetchOpeningSchedule(string $parkingId): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            'SELECT day_of_week, end_day_of_week, open_time, close_time
             FROM opening_hours WHERE parking_id = :id'
        );
        $stmt->execute(['id' => $parkingId]);

        $schedule = [];
        while ($row = $stmt->fetch()) {
            $schedule[] = [
                'start_day' => (int) $row['day_of_week'],
                'end_day' => isset($row['end_day_of_week']) ? (int) $row['end_day_of_week'] : (int) $row['day_of_week'],
                'start_time' => substr((string) $row['open_time'], 0, 5),
                'end_time' => substr((string) $row['close_time'], 0, 5),
            ];
        }

        if ($schedule === []) {
            return \App\Domain\ValueObject\OpeningSchedule::alwaysOpen()->toArray();
        }

        return $schedule;
    }

    private function upsertPricingPlan(PDO $pdo, Parking $parking): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO parking_pricing_plans (parking_id, plan_json, created_at, updated_at)
             VALUES (:parking_id, :plan_json, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                plan_json = VALUES(plan_json),
                updated_at = VALUES(updated_at)'
        );

        $stmt->execute([
            'parking_id' => $parking->getId()->getValue(),
            'plan_json' => json_encode($parking->getPricingPlan()->toArray()),
            'created_at' => $parking->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $parking->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    private function replaceOpeningHours(PDO $pdo, Parking $parking): void
    {
        $pdo->prepare('DELETE FROM opening_hours WHERE parking_id = :id')->execute(['id' => $parking->getId()->getValue()]);

        $schedule = $parking->getOpeningSchedule()->toArray();
        $insert = $pdo->prepare(
            'INSERT INTO opening_hours (parking_id, day_of_week, end_day_of_week, open_time, close_time)
             VALUES (:parking_id, :day, :end_day, :open_time, :close_time)'
        );

        foreach ($schedule as $slot) {
            $insert->execute([
                'parking_id' => $parking->getId()->getValue(),
                'day' => (int) $slot['start_day'],
                'end_day' => (int) $slot['end_day'],
                'open_time' => $this->normalizeTimeForSql($slot['start_time']),
                'close_time' => $this->normalizeTimeForSql($slot['end_time']),
            ]);
        }
    }

    private function normalizeTimeForSql(string $time): string
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
