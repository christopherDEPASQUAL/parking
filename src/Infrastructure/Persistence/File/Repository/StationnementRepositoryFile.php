<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class StationnementRepositoryFile implements StationnementRepositoryInterface
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

    public function findActiveByUser(UserId $userId, ParkingId $parkingId): ?ParkingSession
    {
        foreach ($this->readAll() as $record) {
            if (($record['user_id'] ?? null) !== $userId->getValue()) {
                continue;
            }
            if (($record['parking_id'] ?? null) !== $parkingId->getValue()) {
                continue;
            }
            if (($record['ended_at'] ?? null) !== null) {
                continue;
            }

            return $this->fromArray($record);
        }

        return null;
    }

    public function listByParking(
        ParkingId $parkingId,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['parking_id'] ?? null) !== $parkingId->getValue()) {
                continue;
            }

            $startedAt = new DateTimeImmutable($record['started_at']);
            $endedAt = isset($record['ended_at']) && $record['ended_at'] !== null
                ? new DateTimeImmutable($record['ended_at'])
                : null;

            if ($from !== null && $endedAt !== null && $endedAt < $from) {
                continue;
            }
            if ($to !== null && $startedAt > $to) {
                continue;
            }

            $result[] = $this->fromArray($record);
        }

        return $result;
    }

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['parking_id'] ?? null) !== $parkingId->getValue()) {
                continue;
            }

            $startedAt = new DateTimeImmutable($record['started_at']);
            $endedAt = isset($record['ended_at']) && $record['ended_at'] !== null
                ? new DateTimeImmutable($record['ended_at'])
                : null;

            if ($startedAt > $at) {
                continue;
            }
            if ($endedAt !== null && $endedAt < $at) {
                continue;
            }

            $result[] = $this->fromArray($record);
        }

        return $result;
    }

    public function listByUser(UserId $userId): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['user_id'] ?? null) === $userId->getValue()) {
                $result[] = $this->fromArray($record);
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

    private function toArray(ParkingSession $session): array
    {
        return [
            'id' => $session->getId()->getValue(),
            'parking_id' => $session->getParkingId()->getValue(),
            'user_id' => $session->getUserId()->getValue(),
            'reservation_id' => $session->getReservationId()?->getValue(),
            'abonnement_id' => $session->getAbonnementId()?->getValue(),
            'started_at' => $session->getStartedAt()->format(DATE_ATOM),
            'ended_at' => $session->getEndedAt()?->format(DATE_ATOM),
            'amount_cents' => $session->getAmount()?->getAmountInCents(),
            'currency' => $session->getAmount()?->getCurrency() ?? 'EUR',
        ];
    }

    private function fromArray(array $record): ParkingSession
    {
        $reservationId = isset($record['reservation_id']) && $record['reservation_id'] !== null
            ? ReservationId::fromString($record['reservation_id'])
            : null;
        $abonnementId = isset($record['abonnement_id']) && $record['abonnement_id'] !== null
            ? AbonnementId::fromString($record['abonnement_id'])
            : null;

        $amount = null;
        if (isset($record['amount_cents']) && $record['amount_cents'] !== null) {
            $amount = Money::fromCents((int) $record['amount_cents'], $record['currency'] ?? 'EUR');
        }

        return ParkingSession::fromPersistence(
            StationnementId::fromString($record['id']),
            ParkingId::fromString($record['parking_id']),
            UserId::fromString($record['user_id']),
            $reservationId,
            $abonnementId,
            new DateTimeImmutable($record['started_at']),
            isset($record['ended_at']) && $record['ended_at'] !== null
                ? new DateTimeImmutable($record['ended_at'])
                : null,
            $amount
        );
    }
}
