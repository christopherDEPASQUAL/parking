<?php declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSpot;
use App\Domain\Exception\ParkingFullException;
use App\Domain\Exception\SpotAlreadyExistsException;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class ParkingTest extends TestCase
{
    private ParkingId $parkingId;
    private PricingPlan $pricingPlan;
    private GeoLocation $location;
    private OpeningSchedule $schedule;
    private UserId $userId;

    protected function setUp(): void
    {
        $this->parkingId = ParkingId::fromString('11111111-1111-4111-8111-111111111111');
        $this->pricingPlan = new PricingPlan(
            [
                ['upToMinutes' => 15, 'pricePerStepCents' => 100],
                ['upToMinutes' => 60, 'pricePerStepCents' => 80],
            ],
            50
        );
        $this->location = new GeoLocation(48.8566, 2.3522);
        $this->schedule = OpeningSchedule::alwaysOpen();
        $this->userId = UserId::fromString('22222222-2222-4222-8222-222222222222');
    }

    public function testTouchUpdatesUpdatedAt(): void
    {
        $parking = $this->createParking(5);
        $initial = $parking->getUpdatedAt();

        $reflection = new \ReflectionClass(Parking::class);
        $touch = $reflection->getMethod('touch');
        $touch->setAccessible(true);
        usleep(1000);
        $touch->invoke($parking);

        self::assertGreaterThanOrEqual($initial->getTimestamp(), $parking->getUpdatedAt()->getTimestamp());
    }

    public function testConstructValidParking(): void
    {
        $parking = $this->createParking(10, 'Parking Test');

        self::assertInstanceOf(Parking::class, $parking);
        self::assertSame(10, $parking->getTotalCapacity());
        self::assertSame('Parking Test', $parking->getName());
    }

    public function testConstructInvalidCapacityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createParking(0);
    }

    public function testAddSpotSuccess(): void
    {
        $parking = $this->createParking(5);
        $spot = new ParkingSpot(ParkingSpotId::fromString('33333333-3333-4333-8333-333333333333'));

        $parking->addSpot($spot);

        self::assertSame(4, $parking->getPhysicalFreeSpotsCount());
    }

    public function testAddSpotThrowsExceptionIfDuplicate(): void
    {
        $parking = $this->createParking(5);
        $spot = new ParkingSpot(ParkingSpotId::fromString('44444444-4444-4444-8444-444444444444'));

        $this->expectException(SpotAlreadyExistsException::class);

        $parking->addSpot($spot);
        $parking->addSpot($spot);
    }

    public function testAddSpotThrowsExceptionIfFull(): void
    {
        $parking = $this->createParking(1);
        $spot1 = new ParkingSpot(ParkingSpotId::fromString('55555555-5555-4555-8555-555555555555'));
        $spot2 = new ParkingSpot(ParkingSpotId::fromString('66666666-6666-4666-8666-666666666666'));

        $this->expectException(ParkingFullException::class);

        $parking->addSpot($spot1);
        $parking->addSpot($spot2);
    }

    public function testFreeSpotsAt(): void
    {
        $parking = $this->createParking(10);
        $now = new \DateTimeImmutable();

        $activeRes = new class {
            public function isActiveAt(\DateTimeImmutable $d): bool { return true; }
        };
        $inactiveRes = new class {
            public function isActiveAt(\DateTimeImmutable $d): bool { return false; }
        };
        $activeAbo = new class {
            public function covers(\DateTimeImmutable $d): bool { return true; }
        };
        $badObject = new class {};
        $activeStationnement = new class {
            public function isActiveAt(\DateTimeImmutable $d): bool { return true; }
        };

        $freeSpots = $parking->freeSpotsAt(
            $now,
            [$activeRes, $inactiveRes, $badObject],
            [$activeAbo],
            [$activeStationnement]
        );

        self::assertSame(7, $freeSpots);
    }

    public function testChangePricingPlanAndOpeningSchedule(): void
    {
        $parking = $this->createParking(10);
        $newPlan = new PricingPlan(
            [
                ['upToMinutes' => 15, 'pricePerStepCents' => 50],
                ['upToMinutes' => 120, 'pricePerStepCents' => 40],
            ],
            30
        );
        $customSchedule = new OpeningSchedule([
            1 => [['start' => '08:00', 'end' => '18:00']],
        ]);

        $parking->changePricingPlan($newPlan);
        $parking->changeOpeningSchedule($customSchedule);

        self::assertSame($newPlan, $parking->getPricingPlan());
        self::assertSame($customSchedule, $parking->getOpeningSchedule());
        self::assertTrue($parking->isOpenAt(new \DateTimeImmutable('next monday 10:00')));
        self::assertFalse($parking->isOpenAt(new \DateTimeImmutable('next monday 20:00')));
    }

    public function testAttachIdentifiersAndAvailability(): void
    {
        $parking = $this->createParking(10);
        $parking->attachReservation(\App\Domain\ValueObject\ReservationId::fromString('77777777-7777-4777-8777-777777777777'));
        $parking->attachAbonnement(\App\Domain\ValueObject\AbonnementId::fromString('88888888-8888-4888-8888-888888888888'));
        $parking->attachStationnement(\App\Domain\ValueObject\StationnementId::fromString('99999999-9999-4999-8999-999999999999'));

        self::assertSame(4, $parking->computeAvailability(3, 2, 1));
    }

    public function testComputePriceForDurationMinutes(): void
    {
        $parking = $this->createParking(10);
        $price = $parking->computePriceForDurationMinutes(45);

        self::assertSame(260, $price); // 15 min at 100 + 30 min at 80 (per 15 min)
    }

    public function testGettersExposeBasicInfo(): void
    {
        $parking = $this->createParking(12, 'Central');

        self::assertSame($this->parkingId, $parking->getId());
        self::assertSame('Central', $parking->getName());
        self::assertSame('1 rue du Test', $parking->getAddress());
        self::assertSame($this->location, $parking->getLocation());
        self::assertSame($this->userId, $parking->getUserId());
        self::assertInstanceOf(\DateTimeImmutable::class, $parking->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $parking->getUpdatedAt());
        self::assertFalse($parking->isFull());
    }

    private function createParking(int $capacity, string $name = 'P'): Parking
    {
        return new Parking(
            $this->parkingId,
            $name,
            '1 rue du Test',
            $capacity,
            $this->pricingPlan,
            $this->location,
            $this->schedule,
            $this->userId
        );
    }
}
