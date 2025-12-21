<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\Abonnement;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class AbonnementRepositoryFile implements AbonnementRepositoryInterface
{
    private string $filePath;
    private SubscriptionOfferRepositoryInterface $offerRepository;

    public function __construct(
        ?string $filePath = null,
        ?SubscriptionOfferRepositoryInterface $offerRepository = null
    )
    {
        $resolved = $filePath ?? (getenv('JSON_ABONNEMENT_STORAGE') ?: 'storage/abonnements.json');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 5) . '/' . ltrim($resolved, '/\\');
        }

        $this->filePath = $resolved;
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_file($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }

        $this->offerRepository = $offerRepository ?? new SubscriptionOfferRepositoryFile();
    }

    public function save(Abonnement $abonnement): void
    {
        $records = $this->readAll();
        $records[$abonnement->id()->getValue()] = $this->toArray($abonnement);
        $this->persist($records);
    }

    public function findById(AbonnementId $id): ?Abonnement
    {
        $records = $this->readAll();
        if (!isset($records[$id->getValue()])) {
            return null;
        }

        return $this->fromArray($records[$id->getValue()]);
    }

    public function listByParking(ParkingId $parkingId, ?string $status = null): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['parking_id'] ?? null) !== $parkingId->getValue()) {
                continue;
            }
            if ($status !== null && ($record['status'] ?? null) !== $status) {
                continue;
            }
            $result[] = $this->fromArray($record);
        }

        return $result;
    }

    public function listByUser(UserId $userId, ?string $status = null): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['user_id'] ?? null) !== $userId->getValue()) {
                continue;
            }
            if ($status !== null && ($record['status'] ?? null) !== $status) {
                continue;
            }
            $result[] = $this->fromArray($record);
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

    private function readAll(): array
    {
        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function toArray(Abonnement $abonnement): array
    {
        return [
            'id' => $abonnement->id()->getValue(),
            'user_id' => $abonnement->userId()->getValue(),
            'parking_id' => $abonnement->parkingId()->getValue(),
            'offer_id' => $abonnement->offerId()->getValue(),
            'start_date' => $abonnement->startDate()->format(DATE_ATOM),
            'end_date' => $abonnement->endDate()->format(DATE_ATOM),
            'status' => $abonnement->status(),
        ];
    }

    private function fromArray(array $record): Abonnement
    {
        $offerId = SubscriptionOfferId::fromString($record['offer_id'] ?? throw new \InvalidArgumentException('offer_id is required'));
        $slots = $this->resolveOfferSlots($offerId, $record);

        return new Abonnement(
            AbonnementId::fromString($record['id']),
            UserId::fromString($record['user_id']),
            ParkingId::fromString($record['parking_id']),
            $offerId,
            $slots,
            new DateTimeImmutable($record['start_date']),
            new DateTimeImmutable($record['end_date']),
            $record['status'] ?? 'active'
        );
    }

    /**
     * @param array<int, array<string, mixed>> $slots
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    private function resolveOfferSlots(SubscriptionOfferId $offerId, array $record): array
    {
        $offer = $this->offerRepository->findById($offerId);
        if ($offer !== null) {
            return $offer->weeklyTimeSlots();
        }

        $slots = $record['weekly_time_slots'] ?? [];
        if (!is_array($slots)) {
            return [];
        }

        $normalized = [];
        foreach ($slots as $slot) {
            if (isset($slot['start_day'], $slot['end_day'], $slot['start_time'], $slot['end_time'])) {
                $normalized[] = [
                    'start_day' => (int) $slot['start_day'],
                    'end_day' => (int) $slot['end_day'],
                    'start_time' => (string) $slot['start_time'],
                    'end_time' => (string) $slot['end_time'],
                ];
                continue;
            }

            if (isset($slot['day'], $slot['start'], $slot['end'])) {
                $day = (int) $slot['day'];
                $normalized[] = [
                    'start_day' => $day,
                    'end_day' => $day,
                    'start_time' => (string) $slot['start'],
                    'end_time' => (string) $slot['end'],
                ];
            }
        }

        return $normalized;
    }
}
