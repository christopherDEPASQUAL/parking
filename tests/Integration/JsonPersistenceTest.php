<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\Enum\ReservationStatus;
use App\Infrastructure\Persistence\RepositoryFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class JsonPersistenceTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = \dirname(__DIR__, 2);
        putenv('PERSISTENCE_DRIVER=json');
        $this->resetJsonStorage();
    }

    public function test_persist_and_load_parking_and_reservation_json_driver(): void
    {
        $parkingRepo = RepositoryFactory::createParkingRepository('json');
        $reservationRepo = RepositoryFactory::createReservationRepository('json');

        $parking = $this->buildParking();
        $parkingRepo->save($parking);

        $loadedParking = $parkingRepo->findById($parking->getId());
        self::assertNotNull($loadedParking);
        self::assertSame($parking->getName(), $loadedParking->getName());
        self::assertSame($parking->getDescription(), $loadedParking->getDescription());

        $range = DateRange::fromDateTimes(new DateTimeImmutable('+1 hour'), new DateTimeImmutable('+2 hour'));
        $reservation = new Reservation(
            ReservationId::generate(),
            $parking->getUserId(),
            $parking->getId(),
            $range,
            Money::fromCents(500),
            ReservationStatus::CONFIRMED
        );
        $reservationRepo->save($reservation);

        $loadedReservation = $reservationRepo->findById($reservation->id());
        self::assertNotNull($loadedReservation);
        self::assertTrue($range->overlaps($loadedReservation->dateRange()));
        self::assertSame($reservation->status()->value, $loadedReservation->status()->value);
    }

    private function resetJsonStorage(): void
    {
        $files = [
            $this->baseDir . '/storage/users.json',
            $this->baseDir . '/storage/parkings.json',
            $this->baseDir . '/storage/reservations.json',
        ];

        foreach ($files as $file) {
            $dir = \dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, "{}\n");
        }
    }

    private function buildParking(): Parking
    {
        return new Parking(
            ParkingId::generate(),
            'Json Demo Parking',
            '1 rue du Test',
            10,
            new PricingPlan([['upToMinutes' => 60, 'pricePerStepCents' => 100]], 80),
            new GeoLocation(48.8566, 2.3522),
            OpeningSchedule::alwaysOpen(),
            UserId::generate(),
            'Parking JSON de demo'
        );
    }
}
