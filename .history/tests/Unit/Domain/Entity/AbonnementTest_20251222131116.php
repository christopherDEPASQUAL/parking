<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Abonnement;
use App\Domain\Exception\InvalidAbonnementException;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class AbonnementTest extends TestCase
{
    private AbonnementId $abonnementId;
    private UserId $userId;
    private ParkingId $parkingId;
    private array $weeklyTimeSlots;
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;

    protected function setUp(): void
    {
        $this->abonnementId = AbonnementId::fromString('11111111-1111-4111-8111-111111111111');
        $this->userId = UserId::fromString('22222222-2222-4222-8222-222222222222');
        $this->parkingId = ParkingId::fromString('33333333-3333-4333-8333-333333333333');
        $this->weeklyTimeSlots = [
            ['day' => 1, 'start' => '08:00', 'end' => '18:00'],
            ['day' => 2, 'start' => '08:00', 'end' => '18:00'],
        ];
        $this->startDate = new \DateTimeImmutable('2025-01-01');
        $this->endDate = new \DateTimeImmutable('2025-03-01');
    }

    public function testConstructValidAbonnement(): void
    {
        $abonnement = $this->createAbonnement();

        self::assertInstanceOf(Abonnement::class, $abonnement);
        self::assertSame($this->abonnementId, $abonnement->id());
        self::assertSame($this->userId, $abonnement->userId());
        self::assertSame($this->parkingId, $abonnement->parkingId());
        self::assertSame('active', $abonnement->status());
    }

    public function testConstructWithInvalidDurationThrowsException(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->startDate
        );
    }

    public function testConstructWithTooLongDurationThrowsException(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            new \DateTimeImmutable('2025-01-01'),
            new \DateTimeImmutable('2026-02-01')
        );
    }

    public function testConstructWithEmptyTimeSlotsThrowsException(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            [],
            $this->startDate,
            $this->endDate
        );
    }

    public function testConstructWithInvalidTimeSlotThrowsException(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            [['day' => 1, 'start' => '08:00']],
            $this->startDate,
            $this->endDate
        );
    }

    public function testIsActiveAtReturnsTrueForActiveAbonnement(): void
    {
        $abonnement = $this->createAbonnement();

        self::assertTrue($abonnement->isActiveAt(new \DateTimeImmutable('2025-02-01')));
    }

    public function testIsActiveAtReturnsFalseBeforeStartDate(): void
    {
        $abonnement = $this->createAbonnement();

        self::assertFalse($abonnement->isActiveAt(new \DateTimeImmutable('2024-12-31')));
    }

    public function testIsActiveAtReturnsFalseForSuspendedAbonnement(): void
    {
        $abonnement = $this->createAbonnement();
        $abonnement->suspend();

        self::assertFalse($abonnement->isActiveAt(new \DateTimeImmutable('2025-02-01')));
    }

    public function testCoversTimeSlotReturnsTrueForMatchingSlot(): void
    {
        $abonnement = $this->createAbonnement();

        self::assertTrue($abonnement->coversTimeSlot(new \DateTimeImmutable('2025-01-06 10:00')));
    }

    public function testCoversTimeSlotReturnsFalseForNonMatchingDay(): void
    {
        $abonnement = $this->createAbonnement();

        self::assertFalse($abonnement->coversTimeSlot(new \DateTimeImmutable('2025-01-08 10:00')));
    }

    public function testRenewSuccess(): void
    {
        $abonnement = $this->createAbonnement();
        $newEndDate = new \DateTimeImmutable('2025-06-01');

        $abonnement->renew($newEndDate);

        self::assertSame($newEndDate, $abonnement->endDate());
    }

    public function testRenewWithPastDateThrowsException(): void
    {
        $abonnement = $this->createAbonnement();

        $this->expectException(InvalidAbonnementException::class);

        $abonnement->renew(new \DateTimeImmutable('2025-02-01'));
    }

    public function testSuspendSuccess(): void
    {
        $abonnement = $this->createAbonnement();

        $abonnement->suspend();

        self::assertSame('suspended', $abonnement->status());
    }

    public function testSuspendAlreadySuspendedThrowsException(): void
    {
        $abonnement = $this->createAbonnement();
        $abonnement->suspend();

        $this->expectException(InvalidAbonnementException::class);

        $abonnement->suspend();
    }

    public function testReactivateSuccess(): void
    {
        $abonnement = $this->createAbonnement();
        $abonnement->suspend();

        $abonnement->reactivate();

        self::assertSame('active', $abonnement->status());
    }

    public function testReactivateNonSuspendedThrowsException(): void
    {
        $abonnement = $this->createAbonnement();

        $this->expectException(InvalidAbonnementException::class);

        $abonnement->reactivate();
    }

    public function testExpireSuccess(): void
    {
        $abonnement = $this->createAbonnement();

        $abonnement->expire();

        self::assertSame('expired', $abonnement->status());
    }

    private function createAbonnement(string $status = 'active'): Abonnement
{
    $offerId = new \App\Domain\ValueObject\SubscriptionOfferId('offer_1');

    return new Abonnement(
        $this->abonnementId,
        $this->userId,
        $this->parkingId,
        $offerId,
        $this->weeklyTimeSlots,
        $this->startDate,
        $this->endDate,
        $status
    );
}

}