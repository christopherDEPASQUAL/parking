<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\File\Repository\ParkingRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\ReservationRepositoryFile;
use App\Infrastructure\Persistence\File\Repository\UserRepositoryFile;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;
use App\Infrastructure\Persistence\Sql\Mapper\ParkingMapper;
use App\Infrastructure\Persistence\Sql\Repository\ParkingRepositorySql;
use App\Infrastructure\Persistence\Sql\Repository\ReservationRepositorySql;
use RuntimeException;

/**
 * Simple SQL/JSON switch to pick repository implementations.
 *
 * Driver is resolved via argument or ENV `PERSISTENCE_DRIVER` (json by default).
 */
final class RepositoryFactory
{
    public static function createParkingRepository(?string $driver = null): ParkingRepositoryInterface
    {
        return match (self::resolveDriver($driver)) {
            'sql' => new ParkingRepositorySql(new PdoConnectionFactory(), new ParkingMapper()),
            'json' => new ParkingRepositoryFile(),
            default => throw new RuntimeException('Unsupported persistence driver for Parking'),
        };
    }

    public static function createReservationRepository(?string $driver = null): ReservationRepositoryInterface
    {
        return match (self::resolveDriver($driver)) {
            'json' => new ReservationRepositoryFile(),
            'sql' => new ReservationRepositorySql(new PdoConnectionFactory()),
            default => throw new RuntimeException('Unsupported persistence driver for Reservation'),
        };
    }

    public static function createUserRepository(?string $driver = null): UserRepositoryInterface
    {
        return match (self::resolveDriver($driver)) {
            'json' => new UserRepositoryFile(),
            'sql' => throw new RuntimeException('UserRepository SQL not implemented.'),
            default => throw new RuntimeException('Unsupported persistence driver for User'),
        };
    }

    private static function resolveDriver(?string $driver): string
    {
        $value = strtolower(trim($driver ?? (getenv('PERSISTENCE_DRIVER') ?: 'json')));
        return $value === 'sql' ? 'sql' : 'json';
    }
}
