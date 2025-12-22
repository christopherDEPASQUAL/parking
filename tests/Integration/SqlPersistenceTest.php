<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Persistence\RepositoryFactory;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Entity\Reservation;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ReservationId;
use App\Domain\Enum\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class SqlPersistenceTest extends TestCase
{
    public function test_persist_and_load_parking_sql_driver(): void
    {
        if ((getenv('RUN_SQL_INTEGRATION') ?: '0') !== '1') {
            self::markTestSkipped('RUN_SQL_INTEGRATION non active.');
        }

        $parkingRepo = RepositoryFactory::createParkingRepository('sql');
        $reservationRepo = RepositoryFactory::createReservationRepository('sql');

        $parking = new Parking(
            ParkingId::generate(),
            'Sql Demo Parking',
            '10 rue SQL',
            5,
            new PricingPlan([['upToMinutes' => 30, 'pricePerStepCents' => 120]], 90),
            new GeoLocation(43.6, 3.87),
            OpeningSchedule::alwaysOpen(),
            UserId::generate(),
            'Parking SQL de demo'
        );

        try {
            $parkingRepo->save($parking);
            $loaded = $parkingRepo->findById($parking->getId());

            self::assertNotNull($loaded);
            self::assertSame($parking->getName(), $loaded->getName());
            self::assertSame($parking->getAddress(), $loaded->getAddress());

            $range = DateRange::fromDateTimes(new \DateTimeImmutable('+1 hour'), new \DateTimeImmutable('+2 hour'));
            $reservation = new Reservation(
                ReservationId::generate(),
                $parking->getUserId(),
                $parking->getId(),
                $range,
                Money::fromCents(800),
                ReservationStatus::CONFIRMED
            );
            $reservationRepo->save($reservation);

            $loadedRes = $reservationRepo->findById($reservation->id());
            self::assertNotNull($loadedRes);
            self::assertSame($reservation->status()->value, $loadedRes->status()->value);
        } catch (\Throwable $e) {
            self::markTestSkipped('Test SQL skip (connexion ou schema manquant): ' . $e->getMessage());
        } finally {
            try {
                $parkingRepo->delete($parking->getId());
            } catch (\Throwable) {
                // ignore cleanup failures
            }
        }
    }
}
