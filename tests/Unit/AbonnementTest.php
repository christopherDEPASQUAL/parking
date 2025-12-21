<?php declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Abonnement;
use App\Domain\Exception\InvalidAbonnementException;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AbonnementTest extends TestCase
{
    private AbonnementId $abonnementId;
    private UserId $userId;
    private ParkingId $parkingId;
    private array $weeklyTimeSlots;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;

    protected function setUp(): void
    {
        $this->abonnementId = AbonnementId::fromString('11111111-1111-4111-8111-111111111111');
        $this->userId = UserId::fromString('22222222-2222-4222-8222-222222222222');
        $this->parkingId = ParkingId::fromString('33333333-3333-4333-8333-333333333333');
        $this->weeklyTimeSlots = [
            ['day' => 1, 'start' => '09:00', 'end' => '18:00'],
            ['day' => 5, 'start' => '18:00', 'end' => '23:59']
        ];
        $this->startDate = new DateTimeImmutable('2024-01-01');
        $this->endDate = new DateTimeImmutable('2024-02-01');
    }

    public function testConstructCreatesAbonnement(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        self::assertSame($this->abonnementId, $abonnement->id());
        self::assertSame($this->userId, $abonnement->userId());
        self::assertSame($this->parkingId, $abonnement->parkingId());
        self::assertSame($this->weeklyTimeSlots, $abonnement->weeklyTimeSlots());
        self::assertSame($this->startDate, $abonnement->startDate());
        self::assertSame($this->endDate, $abonnement->endDate());
        self::assertSame('active', $abonnement->status());
    }

    public function testConstructThrowsExceptionIfEndDateBeforeStartDate(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->endDate,
            $this->startDate
        );
    }

    public function testConstructThrowsExceptionIfDurationLessThanOneMonth(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('La durée minimale d\'un abonnement est de 1 mois.');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->startDate->modify('+15 days')
        );
    }

    public function testConstructThrowsExceptionIfDurationMoreThanTwelveMonths(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('La durée maximale d\'un abonnement est de 12 mois.');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->startDate->modify('+13 months')
        );
    }

    public function testConstructThrowsExceptionIfTimeSlotsEmpty(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('Au moins un créneau horaire doit être défini.');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            [],
            $this->startDate,
            $this->endDate
        );
    }

    public function testConstructThrowsExceptionIfTimeSlotMissingFields(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('Chaque créneau doit contenir day, start et end.');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            [['day' => 1]],
            $this->startDate,
            $this->endDate
        );
    }

    public function testConstructThrowsExceptionIfDayOutOfRange(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('Le jour doit être compris entre 1 (lundi) et 7 (dimanche).');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            [['day' => 0, 'start' => '09:00', 'end' => '18:00']],
            $this->startDate,
            $this->endDate
        );
    }

    public function testConstructThrowsExceptionIfStartTimeAfterEndTime(): void
    {
        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('L\'heure de début doit être antérieure à l\'heure de fin.');

        new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            [['day' => 1, 'start' => '18:00', 'end' => '09:00']],
            $this->startDate,
            $this->endDate
        );
    }

    public function testIsActiveAtReturnsTrueForActiveAbonnementInDateRange(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $testDate = $this->startDate->modify('+15 days');

        self::assertTrue($abonnement->isActiveAt($testDate));
    }

    public function testIsActiveAtReturnsFalseForInactiveStatus(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate,
            'suspended'
        );

        self::assertFalse($abonnement->isActiveAt($this->startDate->modify('+15 days')));
    }

    public function testIsActiveAtReturnsFalseBeforeStartDate(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $beforeStart = $this->startDate->modify('-1 day');

        self::assertFalse($abonnement->isActiveAt($beforeStart));
    }

    public function testIsActiveAtReturnsFalseAfterEndDate(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $afterEnd = $this->endDate->modify('+1 day');

        self::assertFalse($abonnement->isActiveAt($afterEnd));
    }

    public function testCoversTimeSlotReturnsTrueForMatchingDayAndTime(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $monday = new DateTimeImmutable('2024-01-01 12:00:00');

        self::assertTrue($abonnement->coversTimeSlot($monday));
    }

    public function testCoversTimeSlotReturnsFalseForWrongDay(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $wednesday = new DateTimeImmutable('2024-01-03 12:00:00');

        self::assertFalse($abonnement->coversTimeSlot($wednesday));
    }

    public function testCoversTimeSlotReturnsFalseForTimeOutsideSlot(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $mondayEarly = new DateTimeImmutable('2024-01-01 08:00:00');

        self::assertFalse($abonnement->coversTimeSlot($mondayEarly));
    }

    public function testRenewExtendsEndDate(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $newEndDate = $this->endDate->modify('+1 month');
        $abonnement->renew($newEndDate);

        self::assertSame($newEndDate, $abonnement->endDate());
    }

    public function testRenewThrowsExceptionIfNewEndDateBeforeCurrentEndDate(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('La nouvelle date de fin doit être postérieure à la date de fin actuelle.');

        $abonnement->renew($this->endDate->modify('-1 day'));
    }

    public function testRenewThrowsExceptionIfTotalDurationExceedsTwelveMonths(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('La durée maximale d\'un abonnement est de 12 mois.');

        $abonnement->renew($this->startDate->modify('+13 months'));
    }

    public function testSuspendChangesStatusToSuspended(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $abonnement->suspend();

        self::assertSame('suspended', $abonnement->status());
    }

    public function testSuspendThrowsExceptionIfAlreadySuspended(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate,
            'suspended'
        );

        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('L\'abonnement est déjà suspendu.');

        $abonnement->suspend();
    }

    public function testReactivateChangesStatusToActive(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate,
            'suspended'
        );

        $abonnement->reactivate();

        self::assertSame('active', $abonnement->status());
    }

    public function testReactivateThrowsExceptionIfNotSuspended(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $this->expectException(InvalidAbonnementException::class);
        $this->expectExceptionMessage('Seul un abonnement suspendu peut être réactivé.');

        $abonnement->reactivate();
    }

    public function testExpireChangesStatusToExpired(): void
    {
        $abonnement = new Abonnement(
            $this->abonnementId,
            $this->userId,
            $this->parkingId,
            $this->weeklyTimeSlots,
            $this->startDate,
            $this->endDate
        );

        $abonnement->expire();

        self::assertSame('expired', $abonnement->status());
    }
}

