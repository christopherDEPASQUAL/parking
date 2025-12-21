<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class ParkingSessionRepositoryFile implements ParkingSessionRepositoryInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('JSON_STATIONNEMENT_STORAGE') ?: 'storage/stationnements.json');
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

    public function save(ParkingSession $session): void
    {
        $records = $this->readAll();
        $records[$session->getId()->getValue()] = $this->toArray($session);
        $this->persist($records);
    }

    public function findById(StationnementId $id): ?ParkingSession
    {
        $records = $this->readAll();
        if (!isset($records[$id->getValue()])) {
            return null;
        }

        return $this->fromArray($records[$id->getValue()]);
    }

    public function findActiveByUserAndParking(UserId $userId, ParkingId $parkingId): ?ParkingSession
    {
        $records = $this->readAll();

        foreach ($records as $record) {
            if ($record['user_id'] === $userId->getValue()
                && $record['parking_id'] === $parkingId->getValue()
                && empty($record['ended_at'])
            ) {
                return $this->fromArray($record);
            }
        }

        return null;
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
        $atStr = $at->format('Y-m-d H:i:s');

        foreach ($records as $record) {
            if ($record['parking_id'] === $parkingId->getValue()
                && $record['started_at'] <= $atStr
                && (empty($record['ended_at']) || $record['ended_at'] >= $atStr)
            ) {
                $result[] = $this->fromArray($record);
            }
        }

        return $result;
    }

    private function toArray(ParkingSession $session): array
    {
        return [
            'id' => $session->getId()->getValue(),
            'parking_id' => $session->getParkingId()->getValue(),
            'user_id' => $session->getUserId()->getValue(),
            'spot_id' => $session->getSpotId()->getValue(),
            'started_at' => $session->getStartedAt()->format('Y-m-d H:i:s'),
            'ended_at' => $session->getEndedAt()?->format('Y-m-d H:i:s')
        ];
    }

    private function fromArray(array $data): ParkingSession
    {
        $session = ParkingSession::start(
            ParkingId::fromString($data['parking_id']),
            UserId::fromString($data['user_id']),
            ParkingSpotId::fromString($data['spot_id']),
            new DateTimeImmutable($data['started_at'])
        );

        if (!empty($data['ended_at'])) {
            $session->close(new DateTimeImmutable($data['ended_at']));
        }

        return $session;
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

