<?php declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    private ReservationId $reservationId;
    private UserId $userId;
    private ParkingId $parkingId;
    private DateRange $dateRange;
    private Money $price;

    protected function setUp(): void
    {
        $this->reservationId = ReservationId::fromString('11111111-1111-4111-8111-111111111111');
        $this->userId = UserId::fromString('22222222-2222-4222-8222-222222222222');
        $this->parkingId = ParkingId::fromString('33333333-3333-4333-8333-333333333333');
        $this->dateRange = DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            new \DateTimeImmutable('2025-01-01 12:00:00')
        );
        $this->price = Money::fromCents(500);
    }

    public function testConstructValidReservation(): void
    {
        $reservation = $this->createReservation();

        self::assertInstanceOf(Reservation::class, $reservation);
        self::assertSame($this->reservationId, $reservation->id());
        self::assertSame($this->userId, $reservation->userId());
        self::assertSame($this->parkingId, $reservation->parkingId());
        self::assertSame($this->dateRange, $reservation->dateRange());
        self::assertSame($this->price, $reservation->price());
        self::assertSame(ReservationStatus::PENDING, $reservation->status());
        self::assertInstanceOf(\DateTimeImmutable::class, $reservation->createdAt());
        self::assertNull($reservation->cancelledAt());
        self::assertNull($reservation->cancellationReason());
    }

    public function testConfirmPendingReservationSuccess(): void
    {
        $reservation = $this->createReservation();

        $reservation->confirm();

        self::assertSame(ReservationStatus::CONFIRMED, $reservation->status());
    }

    public function testConfirmNonPendingReservationThrowsException(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CONFIRMED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Seules les réservations en attente peuvent être confirmées.');

        $reservation->confirm();
    }

    public function testCancelReservationSuccess(): void
    {
        $reservation = $this->createReservation();

        $reservation->cancel('Changement de plans');

        self::assertSame(ReservationStatus::CANCELLED, $reservation->status());
        self::assertInstanceOf(\DateTimeImmutable::class, $reservation->cancelledAt());
        self::assertSame('Changement de plans', $reservation->cancellationReason());
    }

    public function testCancelReservationWithoutReason(): void
    {
        $reservation = $this->createReservation();

        $reservation->cancel();

        self::assertSame(ReservationStatus::CANCELLED, $reservation->status());
        self::assertNull($reservation->cancellationReason());
    }

    public function testCancelNonCancellableReservationThrowsException(): void
    {
        $reservation = $this->createReservation(ReservationStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cette réservation ne peut plus être annulée.');

        $reservation->cancel();
    }

    public function testMarkAsCompletedSuccess(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CONFIRMED);

        $reservation->markAsCompleted();

        self::assertSame(ReservationStatus::COMPLETED, $reservation->status());
    }

    public function testMarkAsCompletedNonActiveReservationThrowsException(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CANCELLED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Seules les réservations actives peuvent être terminées.');

        $reservation->markAsCompleted();
    }

    public function testMarkPaymentFailedSuccess(): void
    {
        $reservation = $this->createReservation(ReservationStatus::PENDING);

        $reservation->markPaymentFailed();

        self::assertSame(ReservationStatus::PAYMENT_FAILED, $reservation->status());
    }

    public function testMarkPaymentFailedOnCompletedReservationThrowsException(): void
    {
        $reservation = $this->createReservation(ReservationStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Impossible de marquer une réservation terminée comme paiement échoué.');

        $reservation->markPaymentFailed();
    }

    public function testIsActiveReturnsTrueForActiveStatus(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CONFIRMED);

        self::assertTrue($reservation->isActive());
    }

    public function testIsActiveReturnsFalseForInactiveStatus(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CANCELLED);

        self::assertFalse($reservation->isActive());
    }

    public function testIsCancelledReturnsTrueForCancelledStatus(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CANCELLED);

        self::assertTrue($reservation->isCancelled());
    }

    public function testIsCancelledReturnsFalseForNonCancelledStatus(): void
    {
        $reservation = $this->createReservation(ReservationStatus::PENDING);

        self::assertFalse($reservation->isCancelled());
    }

    public function testGettersExposeBasicInfo(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-01 09:00:00');
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::PENDING,
            $createdAt
        );

        self::assertSame($this->reservationId, $reservation->id());
        self::assertSame($this->userId, $reservation->userId());
        self::assertSame($this->parkingId, $reservation->parkingId());
        self::assertSame($this->dateRange, $reservation->dateRange());
        self::assertSame($this->price, $reservation->price());
        self::assertSame(ReservationStatus::PENDING, $reservation->status());
        self::assertSame($createdAt, $reservation->createdAt());
        self::assertNull($reservation->cancelledAt());
        self::assertNull($reservation->cancellationReason());
    }

    private function createReservation(
        ReservationStatus $status = ReservationStatus::PENDING
    ): Reservation {
        return new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            $status
        );
    }
}