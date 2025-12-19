<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

/**
 * JSON implementation of ReservationRepository to demonstrate SQL/JSON interchangeability.
 */
final class ReservationRepositoryFile implements ReservationRepositoryInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('JSON_RESERVATION_STORAGE') ?: 'storage/reservations.json');
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

    public function save(Reservation $reservation): void
    {
        $records = $this->readAll();
        $records[$reservation->id()->getValue()] = $this->toArray($reservation);
        $this->persist($records);
    }

    public function findById(ReservationId $id): ?Reservation
    {
        $records = $this->readAll();
        if (!isset($records[$id->getValue()])) {
            return null;
        }

        return $this->fromArray($records[$id->getValue()]);
    }

    public function hasOverlap(ParkingId $parkingId, DateRange $range): bool
    {
        foreach ($this->readAll() as $record) {
            if ($record['parking_id'] !== $parkingId->getValue()) {
                continue;
            }
            $existing = $this->rangeFromRecord($record);
            if ($existing->overlaps($range)) {
                return true;
            }
        }

        return false;
    }

    public function hasUserOverlap(UserId $userId, DateRange $range, ?ParkingId $parkingId = null): bool
    {
        foreach ($this->readAll() as $record) {
            if ($record['user_id'] !== $userId->getValue()) {
                continue;
            }
            if ($parkingId !== null && $record['parking_id'] !== $parkingId->getValue()) {
                continue;
            }
            $existing = $this->rangeFromRecord($record);
            if ($existing->overlaps($range)) {
                return true;
            }
        }

        return false;
    }

    public function listByParking(
        ParkingId $parkingId,
        ?ReservationStatus $status = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $filtered = [];
        foreach ($this->readAll() as $record) {
            if ($record['parking_id'] !== $parkingId->getValue()) {
                continue;
            }
            if ($status !== null && ($record['status'] ?? '') !== $status->value) {
                continue;
            }

            $range = $this->rangeFromRecord($record);
            if ($from !== null && $range->getEnd() < $from) {
                continue;
            }
            if ($to !== null && $range->getStart() > $to) {
                continue;
            }

            $filtered[] = $this->fromArray($record);
        }

        return array_slice($filtered, $offset, $limit);
    }

    public function listActiveAt(ParkingId $parkingId, DateTimeImmutable $at): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if ($record['parking_id'] !== $parkingId->getValue()) {
                continue;
            }
            $range = $this->rangeFromRecord($record);
            $status = ReservationStatus::from($record['status']);
            if ($range->contains($at) && $status->isActive()) {
                $result[] = $this->fromArray($record);
            }
        }

        return $result;
    }

    public function listByUser(UserId $userId): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if ($record['user_id'] === $userId->getValue()) {
                $result[] = $this->fromArray($record);
            }
        }

        return $result;
    }

    public function countByParking(
        ParkingId $parkingId,
        ?ReservationStatus $status = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): int {
        return \count($this->listByParking($parkingId, $status, $from, $to, PHP_INT_MAX, 0));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readAll(): array
    {
        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id()->getValue(),
            'user_id' => $reservation->userId()->getValue(),
            'parking_id' => $reservation->parkingId()->getValue(),
            'start' => $reservation->dateRange()->getStart()->format(DATE_ATOM),
            'end' => $reservation->dateRange()->getEnd()->format(DATE_ATOM),
            'price_cents' => $reservation->price()->getAmountInCents(),
            'currency' => $reservation->price()->getCurrency(),
            'status' => $reservation->status()->value,
            'created_at' => $reservation->createdAt()->format(DATE_ATOM),
            'cancelled_at' => $reservation->cancelledAt()?->format(DATE_ATOM),
            'cancellation_reason' => $reservation->cancellationReason(),
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    private function fromArray(array $record): Reservation
    {
        $range = DateRange::fromDateTimes(
            new DateTimeImmutable($record['start']),
            new DateTimeImmutable($record['end'])
        );

        $reservation = new Reservation(
            ReservationId::fromString($record['id']),
            UserId::fromString($record['user_id']),
            ParkingId::fromString($record['parking_id']),
            $range,
            Money::fromCents((int) $record['price_cents'], $record['currency'] ?? 'EUR'),
            ReservationStatus::from($record['status']),
            isset($record['created_at']) ? new DateTimeImmutable($record['created_at']) : null
        );

        if (isset($record['cancelled_at']) && $record['cancelled_at'] !== null) {
            $ref = new \ReflectionClass($reservation);

            if ($ref->hasProperty('cancelledAt')) {
                $prop = $ref->getProperty('cancelledAt');
                $prop->setAccessible(true);
                $prop->setValue($reservation, new DateTimeImmutable($record['cancelled_at']));
            }

            if ($ref->hasProperty('cancellationReason')) {
                $propReason = $ref->getProperty('cancellationReason');
                $propReason->setAccessible(true);
                $propReason->setValue($reservation, $record['cancellation_reason'] ?? null);
            }
        }

        return $reservation;
    }

    private function rangeFromRecord(array $record): DateRange
    {
        return DateRange::fromDateTimes(
            new DateTimeImmutable($record['start']),
            new DateTimeImmutable($record['end'])
        );
    }
}
