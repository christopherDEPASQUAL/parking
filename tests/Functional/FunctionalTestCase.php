<?php
declare(strict_types=1);

namespace Tests\Functional;

use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSession;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\User;
use App\Domain\Enum\ReservationStatus;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PasswordHash;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\ReservationId;
use App\Infrastructure\DependencyInjection\Container;
use App\Infrastructure\Http\Router;
use App\Presentation\Http\Controller\Api\ParkingApiController;
use App\Presentation\Http\Controller\Api\ReservationApiController;
use App\Presentation\Http\Controller\Api\StationnementApiController;
use App\Presentation\Http\Middleware\AuthJWTMiddleware;
use App\Application\Port\Services\JwtEncoderInterface;
use PHPUnit\Framework\TestCase;

abstract class FunctionalTestCase extends TestCase
{
    protected Container $container;
    protected Router $router;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/parking-functional-' . bin2hex(random_bytes(4));
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        $this->setEnv('PERSISTENCE_DRIVER', 'json');
        $this->setEnv('JWT_SECRET_KEY', 'test-secret');
        $this->setEnv('JSON_USER_STORAGE', $this->tempDir . '/users.json');
        $this->setEnv('JSON_PARKING_STORAGE', $this->tempDir . '/parkings.json');
        $this->setEnv('JSON_RESERVATION_STORAGE', $this->tempDir . '/reservations.json');
        $this->setEnv('JSON_ABONNEMENT_STORAGE', $this->tempDir . '/abonnements.json');
        $this->setEnv('JSON_SUBSCRIPTION_OFFER_STORAGE', $this->tempDir . '/subscription_offers.json');
        $this->setEnv('JSON_STATIONNEMENT_STORAGE', $this->tempDir . '/stationnements.json');
        $this->setEnv('JSON_PAYMENT_STORAGE', $this->tempDir . '/payments.json');
        $this->setEnv('JWT_BLACKLIST_STORAGE', $this->tempDir . '/jwt_blacklist.json');

        $this->seedEmptyStorage([
            getenv('JSON_USER_STORAGE'),
            getenv('JSON_PARKING_STORAGE'),
            getenv('JSON_RESERVATION_STORAGE'),
            getenv('JSON_ABONNEMENT_STORAGE'),
            getenv('JSON_SUBSCRIPTION_OFFER_STORAGE'),
            getenv('JSON_STATIONNEMENT_STORAGE'),
            getenv('JSON_PAYMENT_STORAGE'),
            getenv('JWT_BLACKLIST_STORAGE'),
        ]);

        $this->container = new Container();
        $this->router = new Router($this->container);
        $this->registerRoutes();
    }

    protected function tearDown(): void
    {
        $this->cleanupGlobals();
        $this->cleanupEnv();

        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*') ?: [];
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    protected function dispatch(string $method, string $path): array
    {
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;

        ob_start();
        $this->router->dispatch($method, $path);
        $output = (string) ob_get_clean();
        $decoded = json_decode($output, true);

        return is_array($decoded) ? $decoded : ['raw' => $output];
    }

    protected function authenticate(User $user): void
    {
        $jwt = $this->container->get(JwtEncoderInterface::class);
        $token = $jwt->generateAccessToken(
            $user->getId()->getValue(),
            (string) $user->getEmail(),
            $user->getRole()->value
        );
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    protected function createUser(UserRole $role, string $email): User
    {
        $user = User::register(
            Email::fromString($email),
            PasswordHash::fromPlainText('password123'),
            $role,
            'Test',
            'User'
        );

        $this->container->get(UserRepositoryInterface::class)->save($user);

        return $user;
    }

    protected function createParking(User $owner, string $parkingId = 'parking-1'): Parking
    {
        $parking = new Parking(
            ParkingId::fromString($parkingId),
            'Test Parking',
            '1 rue de Paris',
            10,
            new PricingPlan([], 200),
            new GeoLocation(48.8566, 2.3522),
            \App\Domain\ValueObject\OpeningSchedule::alwaysOpen(),
            $owner->getId()
        );

        $this->container->get(ParkingRepositoryInterface::class)->save($parking);

        return $parking;
    }

    protected function createReservation(User $user, Parking $parking): Reservation
    {
        $range = DateRange::fromDateTimes(
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            new \DateTimeImmutable('2025-01-01 12:00:00')
        );

        $reservation = new Reservation(
            ReservationId::generate(),
            $user->getId(),
            $parking->getId(),
            $range,
            Money::fromCents(1000),
            ReservationStatus::CONFIRMED
        );

        $this->container->get(ReservationRepositoryInterface::class)->save($reservation);

        return $reservation;
    }

    protected function createSession(User $user, Parking $parking, Reservation $reservation): ParkingSession
    {
        $session = ParkingSession::start(
            $parking->getId(),
            $user->getId(),
            $reservation->id(),
            null,
            new \DateTimeImmutable('2025-01-01 10:15:00')
        );

        $this->container->get(StationnementRepositoryInterface::class)->save($session);

        return $session;
    }

    private function registerRoutes(): void
    {
        $authMiddleware = [AuthJWTMiddleware::class];

        $this->router->addRoute('GET', '/reservations/me', ReservationApiController::class, 'listMine', $authMiddleware);
        $this->router->addRoute('GET', '/stationings/me', StationnementApiController::class, 'listMine', $authMiddleware);
        $this->router->addRoute('GET', '/owner/parkings', ParkingApiController::class, 'listOwner', $authMiddleware);
        $this->router->addRoute('GET', '/owner/parkings/{id}/reservations', ReservationApiController::class, 'listByParking', $authMiddleware);
    }

    private function seedEmptyStorage(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($path, json_encode([]));
        }
    }

    private function setEnv(string $key, string $value): void
    {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }

    private function cleanupEnv(): void
    {
        $keys = [
            'PERSISTENCE_DRIVER',
            'JWT_SECRET_KEY',
            'JSON_USER_STORAGE',
            'JSON_PARKING_STORAGE',
            'JSON_RESERVATION_STORAGE',
            'JSON_ABONNEMENT_STORAGE',
            'JSON_SUBSCRIPTION_OFFER_STORAGE',
            'JSON_STATIONNEMENT_STORAGE',
            'JSON_PAYMENT_STORAGE',
            'JWT_BLACKLIST_STORAGE',
        ];

        foreach ($keys as $key) {
            putenv($key);
            unset($_ENV[$key]);
        }
    }

    private function cleanupGlobals(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['AUTH_USER_ID'],
            $_SERVER['AUTH_USER_ROLE'],
            $_SERVER['AUTH_USER_EMAIL'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI']
        );
        $_GET = [];
        header_remove();
    }
}
