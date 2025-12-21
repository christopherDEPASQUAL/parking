<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\Abonnement;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class AbonnementRepositoryFile implements AbonnementRepositoryInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
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

    public function listByUser(UserId $userId): array
    {
        $records = $this->readAll();
        $result = [];

        foreach ($records as $record) {
            if ($record['user_id'] === $userId->getValue()) {
                $result[] = $this->fromArray($record);
            }
        }

        return $result;
    }

    public function listByParking(ParkingId $parkingId): array
    {
        $records = $this->readAll();
        $result = [];

        foreach ($records as $record) {
            if ($record['parking_id'] === $parkingId->getValue()) {
                $result[] = $this->fromArray($record);
            }
        }

        return $result;
    }

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        $records = $this->readAll();
        $result = [];

        foreach ($records as $record) {
            if ($record['parking_id'] === $parkingId->getValue()
                && $record['status'] === 'active'
            ) {
                $abonnement = $this->fromArray($record);
                if ($abonnement->coversTimeSlot($at)) {
                    $result[] = $abonnement;
                }
            }
        }

        return $result;
    }

    private function toArray(Abonnement $abonnement): array
    {
        return [
            'id' => $abonnement->id()->getValue(),
            'user_id' => $abonnement->userId()->getValue(),
            'parking_id' => $abonnement->parkingId()->getValue(),
            'weekly_time_slots' => $abonnement->weeklyTimeSlots(),
            'start_date' => $abonnement->startDate()->format('Y-m-d'),
            'end_date' => $abonnement->endDate()->format('Y-m-d'),
            'status' => $abonnement->status()
        ];
    }

    private function fromArray(array $data): Abonnement
    {
        return new Abonnement(
            AbonnementId::fromString($data['id']),
            UserId::fromString($data['user_id']),
            ParkingId::fromString($data['parking_id']),
            $data['weekly_time_slots'] ?? [],
            new DateTimeImmutable($data['start_date']),
            new DateTimeImmutable($data['end_date']),
            $data['status'] ?? 'active'
        );
    }

    private function readAll(): array
    {
        $content = file_get_contents($this->filePath);
        return json_decode($content, true) ?: [];
    }

    private function persist(array $records): void
    {
        file_put_contents($this->filePath, json_encode($records, JSON_PRETTY_PRINT));
    }
}

