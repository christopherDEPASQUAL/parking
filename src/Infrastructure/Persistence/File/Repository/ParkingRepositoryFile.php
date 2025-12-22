<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\Parking;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSearchQuery;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\Sql\Mapper\ParkingMapper;
use DateTimeImmutable;

final class ParkingRepositoryFile implements ParkingRepositoryInterface
{
    private ParkingMapper $mapper;
    private string $filePath;
    private ReservationRepositoryInterface $reservationRepository;
    private AbonnementRepositoryInterface $abonnementRepository;
    private StationnementRepositoryInterface $stationnementRepository;
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(
        ?string $filePath = null,
        ?ParkingMapper $mapper = null,
        ?ReservationRepositoryInterface $reservationRepository = null,
        ?AbonnementRepositoryInterface $abonnementRepository = null,
        ?StationnementRepositoryInterface $stationnementRepository = null,
        ?PaymentRepositoryInterface $paymentRepository = null
    ) {
        $resolved = $filePath ?? (getenv('JSON_PARKING_STORAGE') ?: 'storage/parkings.json');
        if (!preg_match('#^([A-Za-z]:\\|/)#', $resolved)) {
            $resolved = 
                \dirname(__DIR__, 5) . '/' . ltrim($resolved, '/\\');
        }
        $this->filePath = $resolved;
        $this->mapper = $mapper ?? new ParkingMapper();
        $this->reservationRepository = $reservationRepository ?? new ReservationRepositoryFile();
        $this->abonnementRepository = $abonnementRepository ?? new AbonnementRepositoryFile();
        $this->stationnementRepository = $stationnementRepository ?? new StationnementRepositoryFile();
        $this->paymentRepository = $paymentRepository ?? new PaymentRepositoryFile();
    }

    public function save(Parking $parking): void
    {
        $data = $this->load();
        $data[$parking->getId()->getValue()] = $this->mapper->toArray($parking);
        $this->persist($data);
    }

    public function delete(ParkingId $parkingId): void
    {
        $data = $this->load();
        unset($data[$parkingId->getValue()]);
        $this->persist($data);
    }

    public function findById(ParkingId $parkingId): ?Parking
    {
        $data = $this->load();
        if (!isset($data[$parkingId->getValue()])) {
            return null;
        }

        return $this->mapper->fromArray($data[$parkingId->getValue()]);
    }

    public function findByOwnerId(UserId $ownerId): array
    {
        $result = [];
        foreach ($this->load() as $parking) {
            if (($parking['owner_id'] ?? null) === $ownerId->getValue()) {
                $result[] = $this->mapper->fromArray($parking);
            }
        }

        return $result;
    }

    public function searchAvailable(ParkingSearchQuery $query): array
    {
        $nameFilter = $query->name();
        $rangeEnd = $query->endsAt();
        $rangeStart = $query->at();
        $result = [];
        foreach ($this->load() as $raw) {
            $parking = $this->mapper->fromArray($raw);

            if ($query->ownerId() !== null && !$parking->getUserId()->equals($query->ownerId())) {
                continue;
            }

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

    private function load(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
