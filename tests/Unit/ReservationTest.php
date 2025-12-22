<?php declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;
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
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable('+2 hours')
        );
        $this->price = Money::fromFloat(10.50);
    }

    public function testConstructCreatesReservationWithPendingStatus(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price
        );

        self::assertSame($this->reservationId, $reservation->id());
        self::assertSame($this->userId, $reservation->userId());
        self::assertSame($this->parkingId, $reservation->parkingId());
        self::assertSame($this->dateRange, $reservation->dateRange());
        self::assertSame($this->price, $reservation->price());
        self::assertEquals(ReservationStatus::PENDING, $reservation->status());
        self::assertInstanceOf(DateTimeImmutable::class, $reservation->createdAt());
        self::assertNull($reservation->cancelledAt());
        self::assertNull($reservation->cancellationReason());
    }

    public function testConstructWithCustomStatus(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CONFIRMED
        );

        self::assertEquals(ReservationStatus::CONFIRMED, $reservation->status());
    }

    public function testConfirmChangesStatusFromPendingToConfirmed(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::PENDING
        );

        $reservation->confirm();

        self::assertEquals(ReservationStatus::CONFIRMED, $reservation->status());
    }

    public function testConfirmThrowsExceptionIfNotPending(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CONFIRMED
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Seules les réservations en attente peuvent être confirmées.');

        $reservation->confirm();
    }

    public function testCancelChangesStatusToCancelled(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::PENDING
        );

        $reservation->cancel('Raison d\'annulation');

        self::assertEquals(ReservationStatus::CANCELLED, $reservation->status());
        self::assertNotNull($reservation->cancelledAt());
        self::assertSame('Raison d\'annulation', $reservation->cancellationReason());
    }

    public function testCancelWithoutReason(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CONFIRMED
        );

        $reservation->cancel();

        self::assertEquals(ReservationStatus::CANCELLED, $reservation->status());
        self::assertNull($reservation->cancellationReason());
    }

    public function testCancelThrowsExceptionIfCannotBeCancelled(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::COMPLETED
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cette réservation ne peut plus être annulée.');

        $reservation->cancel();
    }

    public function testMarkAsCompletedChangesStatusToCompleted(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CONFIRMED
        );

        $reservation->markAsCompleted();

        self::assertEquals(ReservationStatus::COMPLETED, $reservation->status());
    }

    public function testMarkAsCompletedThrowsExceptionIfNotActive(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CANCELLED
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Seules les réservations actives peuvent être terminées.');

        $reservation->markAsCompleted();
    }

    public function testMarkPaymentFailedChangesStatusToPaymentFailed(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::PENDING
        );

        $reservation->markPaymentFailed();

        self::assertEquals(ReservationStatus::PAYMENT_FAILED, $reservation->status());
    }

    public function testMarkPaymentFailedThrowsExceptionIfCompleted(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::COMPLETED
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Impossible de marquer une réservation terminée comme paiement échoué.');

        $reservation->markPaymentFailed();
    }

    public function testIsActiveReturnsTrueForPendingAndConfirmed(): void
    {
        $pending = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::PENDING
        );

        $confirmed = new Reservation(
            ReservationId::generate(),
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CONFIRMED
        );

        self::assertTrue($pending->isActive());
        self::assertTrue($confirmed->isActive());
    }

    public function testIsActiveReturnsFalseForCancelledAndCompleted(): void
    {
        $cancelled = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CANCELLED
        );

        $completed = new Reservation(
            ReservationId::generate(),
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::COMPLETED
        );

        self::assertFalse($cancelled->isActive());
        self::assertFalse($completed->isActive());
    }

    public function testIsCancelledReturnsTrueForCancelledStatus(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::PENDING
        );

        $reservation->cancel();

        self::assertTrue($reservation->isCancelled());
    }

    public function testIsCancelledReturnsFalseForOtherStatuses(): void
    {
        $reservation = new Reservation(
            $this->reservationId,
            $this->userId,
            $this->parkingId,
            $this->dateRange,
            $this->price,
            ReservationStatus::CONFIRMED
        );

        self::assertFalse($reservation->isCancelled());
    }
}

