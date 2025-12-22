<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\File\Repository\AbonnementRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\ParkingRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\PaymentRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\ReservationRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\StationnementRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\SubscriptionOfferRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\UserRepositoryFile;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use App\Infrastructure\Persistence\Sql\Repository\AbonnementRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\ParkingRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\PaymentRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\ReservationRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\StationnementRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\SubscriptionOfferRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\UserRepositorySql;

final class RepositoryFactory
{
    private static ?PdoConnectionFactory $pdoFactory = null;

    public static function createUserRepository(?string $driver = null): UserRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new UserRepositorySql(self::pdo()),
            'json' => new UserRepositoryFile(),
        };
    }

    public static function createParkingRepository(?string $driver = null): ParkingRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new ParkingRepositorySql(self::pdo()),
            'json' => new ParkingRepositoryFile(),
        };
    }

    public static function createReservationRepository(?string $driver = null): ReservationRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new ReservationRepositorySql(self::pdo()),
            'json' => new ReservationRepositoryFile(),
        };
    }

    public static function createAbonnementRepository(?string $driver = null): AbonnementRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new AbonnementRepositorySql(self::pdo()),
            'json' => new AbonnementRepositoryFile(),
        };
    }

    public static function createStationnementRepository(?string $driver = null): StationnementRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new StationnementRepositorySql(self::pdo()),
            'json' => new StationnementRepositoryFile(),
        };
    }

    public static function createPaymentRepository(?string $driver = null): PaymentRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new PaymentRepositorySql(self::pdo()),
            'json' => new PaymentRepositoryFile(),
        };
    }

    public static function createSubscriptionOfferRepository(?string $driver = null): SubscriptionOfferRepositoryInterface
    {
        return match (self::driver($driver)) {
            'sql' => new SubscriptionOfferRepositorySql(self::pdo()),
            'json' => new SubscriptionOfferRepositoryFile(),
        };
    }

    private static function driver(?string $override = null): string
    {
        $driver = strtolower((string) ($override ?? getenv('PERSISTENCE_DRIVER')));
        if ($driver === '') {
            return 'json';
        }

        if ($driver === 'mysql') {
            return 'sql';
        }

        if (!in_array($driver, ['json', 'sql'], true)) {
            throw new \InvalidArgumentException("Unsupported persistence driver: {$driver}");
        }

        return $driver;
    }

    private static function pdo(): PdoConnectionFactory
    {
        if (self::$pdoFactory === null) {
            self::$pdoFactory = new PdoConnectionFactory();
        }

        return self::$pdoFactory;
    }
}
