<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\ParkingSession;
use App\Domain\Exception\InvalidSessionTimeException;
use App\Domain\Exception\SessionAlreadyClosedException;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class ParkingSessionTest extends TestCase
{
    public function testStartAndCloseSession(): void
    {
        $session = ParkingSession::start(
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            ReservationId::fromString('22222222-2222-4222-8222-222222222222'),
            null,
            new \DateTimeImmutable('2025-01-01 10:00:00')
        );

        self::assertTrue($session->isActive());
        self::assertSame(30, $session->durationMinutes(new \DateTimeImmutable('2025-01-01 10:30:00')));

        $session->close(new \DateTimeImmutable('2025-01-01 11:00:00'), Money::fromCents(500));

        self::assertFalse($session->isActive());
        self::assertSame(500, $session->getAmount()?->getAmountInCents());
    }

    public function testCloseTwiceThrows(): void
    {
        $session = ParkingSession::start(
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            ReservationId::fromString('22222222-2222-4222-8222-222222222222')
        );

        $session->close(new \DateTimeImmutable('+1 hour'));

        $this->expectException(SessionAlreadyClosedException::class);
        $session->close(new \DateTimeImmutable('+2 hours'));
    }

    public function testInvalidEndTimeThrows(): void
    {
        $session = ParkingSession::start(
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            ReservationId::fromString('22222222-2222-4222-8222-222222222222'),
            null,
            new \DateTimeImmutable('2025-01-01 10:00:00')
        );

        $this->expectException(InvalidSessionTimeException::class);
        $session->close(new \DateTimeImmutable('2025-01-01 09:59:00'));
    }

    public function testStartWithInvalidReferencesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ParkingSession::start(
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            ReservationId::fromString('22222222-2222-4222-8222-222222222222'),
            AbonnementId::fromString('33333333-3333-4333-8333-333333333333')
        );
    }

    public function testIsActiveAt(): void
    {
        $session = ParkingSession::start(
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            ReservationId::fromString('22222222-2222-4222-8222-222222222222'),
            null,
            new \DateTimeImmutable('2025-01-01 10:00:00')
        );

        self::assertTrue($session->isActiveAt(new \DateTimeImmutable('2025-01-01 10:05:00')));
        $session->close(new \DateTimeImmutable('2025-01-01 10:30:00'));
        self::assertFalse($session->isActiveAt(new \DateTimeImmutable('2025-01-01 10:45:00')));
    }

    public function testFromPersistence(): void
    {
        $session = ParkingSession::fromPersistence(
            \App\Domain\ValueObject\StationnementId::fromString('44444444-4444-4444-8444-444444444444'),
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            null,
            AbonnementId::fromString('33333333-3333-4333-8333-333333333333'),
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            new \DateTimeImmutable('2025-01-01 11:00:00'),
            Money::fromCents(500)
        );

        self::assertFalse($session->isActive());
        self::assertSame(500, $session->getAmount()?->getAmountInCents());
        self::assertNotNull($session->getAbonnementId());
    }

    public function testGettersExposeState(): void
    {
        $reservationId = ReservationId::fromString('22222222-2222-4222-8222-222222222222');
        $startedAt = new \DateTimeImmutable('2025-01-01 10:00:00');

        $session = ParkingSession::start(
            ParkingId::fromString('parking-1'),
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            $reservationId,
            null,
            $startedAt
        );

        self::assertSame('parking-1', $session->getParkingId()->getValue());
        self::assertSame($reservationId, $session->getReservationId());
        self::assertSame($startedAt, $session->getStartedAt());
        self::assertNull($session->getEndedAt());
    }
}
