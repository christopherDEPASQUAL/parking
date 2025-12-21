<?php declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\ParkingSession;
use App\Domain\Exception\InvalidSessionTimeException;
use App\Domain\Exception\SessionAlreadyClosedException;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ParkingSessionTest extends TestCase
{
    private ParkingId $parkingId;
    private UserId $userId;
    private ParkingSpotId $spotId;
    private DateTimeImmutable $startTime;

    protected function setUp(): void
    {
        $this->parkingId = ParkingId::fromString('11111111-1111-4111-8111-111111111111');
        $this->userId = UserId::fromString('22222222-2222-4222-8222-222222222222');
        $this->spotId = ParkingSpotId::fromString('33333333-3333-4333-8333-333333333333');
        $this->startTime = new DateTimeImmutable('2024-01-01 10:00:00');
    }

    public function testStartCreatesActiveSession(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        self::assertInstanceOf(ParkingSession::class, $session);
        self::assertSame($this->parkingId, $session->getParkingId());
        self::assertSame($this->userId, $session->getUserId());
        self::assertSame($this->spotId, $session->getSpotId());
        self::assertSame($this->startTime, $session->getStartedAt());
        self::assertNull($session->getEndedAt());
        self::assertTrue($session->isActive());
    }

    public function testStartUsesCurrentTimeIfNotProvided(): void
    {
        $before = new DateTimeImmutable();
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId
        );
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $session->getStartedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $session->getStartedAt()->getTimestamp());
    }

    public function testCloseSetsEndTime(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $endTime = $this->startTime->modify('+2 hours');
        $session->close($endTime);

        self::assertSame($endTime, $session->getEndedAt());
        self::assertFalse($session->isActive());
    }

    public function testCloseThrowsExceptionIfAlreadyClosed(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $endTime = $this->startTime->modify('+2 hours');
        $session->close($endTime);

        $this->expectException(SessionAlreadyClosedException::class);
        $this->expectExceptionMessage('Stationnement déjà clôturé.');

        $session->close($endTime->modify('+1 hour'));
    }

    public function testCloseThrowsExceptionIfEndTimeBeforeStartTime(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $invalidEndTime = $this->startTime->modify('-1 hour');

        $this->expectException(InvalidSessionTimeException::class);
        $this->expectExceptionMessage('Heure de fin invalide.');

        $session->close($invalidEndTime);
    }

    public function testCloseThrowsExceptionIfEndTimeEqualsStartTime(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $this->expectException(InvalidSessionTimeException::class);
        $this->expectExceptionMessage('Heure de fin invalide.');

        $session->close($this->startTime);
    }

    public function testDurationMinutesReturnsCorrectDurationForClosedSession(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $endTime = $this->startTime->modify('+90 minutes');
        $session->close($endTime);

        self::assertSame(90, $session->durationMinutes());
    }

    public function testDurationMinutesUsesReferenceTimeForActiveSession(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $referenceTime = $this->startTime->modify('+45 minutes');
        $duration = $session->durationMinutes($referenceTime);

        self::assertSame(45, $duration);
    }

    public function testDurationMinutesUsesCurrentTimeIfNoReferenceProvided(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        usleep(100000);
        $duration = $session->durationMinutes();

        self::assertGreaterThanOrEqual(0, $duration);
    }

    public function testIsActiveReturnsTrueForOpenSession(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        self::assertTrue($session->isActive());
    }

    public function testIsActiveReturnsFalseForClosedSession(): void
    {
        $session = ParkingSession::start(
            $this->parkingId,
            $this->userId,
            $this->spotId,
            $this->startTime
        );

        $session->close($this->startTime->modify('+1 hour'));

        self::assertFalse($session->isActive());
    }
}

