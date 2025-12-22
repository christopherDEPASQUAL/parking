<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\InvalidSessionTimeException;
use App\Domain\Exception\SessionAlreadyClosedException;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class ParkingSession
{
    private StationnementId $id;
    private ParkingId $parkingId;
    private UserId $userId;
    private ?ReservationId $reservationId;
    private ?AbonnementId $abonnementId;
    private DateTimeImmutable $startedAt;
    private ?DateTimeImmutable $endedAt = null;
    private ?Money $amount = null;

    private function __construct(
        StationnementId $id,
        ParkingId $parkingId,
        UserId $userId,
        ?ReservationId $reservationId,
        ?AbonnementId $abonnementId,
        DateTimeImmutable $startedAt
    ) {
        if (($reservationId === null && $abonnementId === null)
            || ($reservationId !== null && $abonnementId !== null)) {
            throw new \InvalidArgumentException('A session must reference exactly one reservation or abonnement.');
        }

        $this->id = $id;
        $this->parkingId = $parkingId;
        $this->userId = $userId;
        $this->reservationId = $reservationId;
        $this->abonnementId = $abonnementId;
        $this->startedAt = $startedAt;
    }

    public static function start(
        ParkingId $parkingId,
        UserId $userId,
        ?ReservationId $reservationId = null,
        ?AbonnementId $abonnementId = null,
        ?DateTimeImmutable $startedAt = null
    ): self {
        return new self(
            StationnementId::generate(),
            $parkingId,
            $userId,
            $reservationId,
            $abonnementId,
            $startedAt ?? new DateTimeImmutable()
        );
    }

    public static function fromPersistence(
        StationnementId $id,
        ParkingId $parkingId,
        UserId $userId,
        ?ReservationId $reservationId,
        ?AbonnementId $abonnementId,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $endedAt = null,
        ?Money $amount = null
    ): self {
        $session = new self(
            $id,
            $parkingId,
            $userId,
            $reservationId,
            $abonnementId,
            $startedAt
        );

        $session->endedAt = $endedAt;
        $session->amount = $amount;

        return $session;
    }

    public function close(DateTimeImmutable $endedAt, ?Money $amount = null): void
    {
        if ($this->endedAt !== null) {
            throw new SessionAlreadyClosedException('Session already closed.');
        }
        if ($endedAt <= $this->startedAt) {
            throw new InvalidSessionTimeException('Invalid end time.');
        }
        $this->endedAt = $endedAt;

        if ($amount !== null) {
            $this->amount = $amount;
        }
    }

    public function isActive(): bool
    {
        return $this->endedAt === null;
    }

    public function isActiveAt(DateTimeImmutable $at): bool
    {
        if ($this->startedAt > $at) {
            return false;
        }

        return $this->endedAt === null || $this->endedAt >= $at;
    }

    public function durationMinutes(?DateTimeImmutable $referenceTime = null): int
    {
        $end = $this->endedAt ?? ($referenceTime ?? new DateTimeImmutable());
        return (int) ceil(($end->getTimestamp() - $this->startedAt->getTimestamp()) / 60);
    }

    public function getId(): StationnementId { return $this->id; }
    public function getParkingId(): ParkingId { return $this->parkingId; }
    public function getUserId(): UserId { return $this->userId; }
    public function getReservationId(): ?ReservationId { return $this->reservationId; }
    public function getAbonnementId(): ?AbonnementId { return $this->abonnementId; }
    public function getStartedAt(): DateTimeImmutable { return $this->startedAt; }
    public function getEndedAt(): ?DateTimeImmutable { return $this->endedAt; }
    public function getAmount(): ?Money { return $this->amount; }
}
