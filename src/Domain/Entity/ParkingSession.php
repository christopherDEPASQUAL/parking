<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\SessionAlreadyClosedException;
use App\Domain\Exception\InvalidSessionTimeException;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class ParkingSession
{
    private StationnementId $id;
    private ParkingId $parkingId;
    private UserId $userId;
    private ParkingSpotId $spotId;
    private DateTimeImmutable $startedAt;
    private ?DateTimeImmutable $endedAt = null;

    private function __construct(
        StationnementId $id,
        ParkingId $parkingId,
        UserId $userId,
        ParkingSpotId $spotId,
        DateTimeImmutable $startedAt
    ) {
        $this->id = $id;
        $this->parkingId = $parkingId;
        $this->userId = $userId;
        $this->spotId = $spotId;
        $this->startedAt = $startedAt;
    }

    public static function start(
        ParkingId $parkingId,
        UserId $userId,
        ParkingSpotId $spotId,
        ?DateTimeImmutable $startedAt = null
    ): self {
        return new self(
            StationnementId::generate(),
            $parkingId,
            $userId,
            $spotId,
            $startedAt ?? new DateTimeImmutable()
        );
    }

    public function close(DateTimeImmutable $endedAt): void
    {
        if ($this->endedAt !== null) {
            throw new SessionAlreadyClosedException('Stationnement déjà clôturé.');
        }
        if ($endedAt <= $this->startedAt) {
            throw new InvalidSessionTimeException('Heure de fin invalide.');
        }
        $this->endedAt = $endedAt;
    }

    public function isActive(): bool
    {
        return $this->endedAt === null;
    }

    /**
     * Durée en minutes. Par défaut, référence = now si non clos.
     */
    public function durationMinutes(?DateTimeImmutable $referenceTime = null): int
    {
        $end = $this->endedAt ?? ($referenceTime ?? new DateTimeImmutable());
        return (int) round(($end->getTimestamp() - $this->startedAt->getTimestamp()) / 60);
    }

    public function getId(): StationnementId { return $this->id; }
    public function getParkingId(): ParkingId { return $this->parkingId; }
    public function getUserId(): UserId { return $this->userId; }
    public function getSpotId(): ParkingSpotId { return $this->spotId; }
    public function getStartedAt(): DateTimeImmutable { return $this->startedAt; }
    public function getEndedAt(): ?DateTimeImmutable { return $this->endedAt; }
}
