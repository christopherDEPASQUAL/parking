<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\RepositoryFactory;
use App\Infrastructure\Security\PasswordHasher;
use App\Infrastructure\Security\JwtEncoder;
use App\Infrastructure\Messaging\SimpleEventDispatcher;
use App\Application\Port\Security\PasswordHasherInterface;
use App\Application\Port\Security\JwtServiceInterface;
use App\Application\Port\Messaging\EventDispatcherInterface;
use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\RefreshToken;
use App\Application\UseCase\Auth\LogoutUser;
use App\Application\UseCase\Parkings\SearchParkings;
use App\Application\UseCase\Parkings\ViewParking;
use App\Application\UseCase\Parkings\CreateParking;
use App\Application\UseCase\Parkings\EnterParking;
use App\Application\UseCase\Parkings\ExitParking;
use App\Application\UseCase\Parkings\ListUserStationnements;
use App\Application\UseCase\Parkings\GetParkingAvailability;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Application\UseCase\Reservations\CreateReservation;
use App\Application\UseCase\Reservations\CancelReservation;
use App\Application\UseCase\Reservations\ListUserReservations;
use App\Application\UseCase\Reservations\GetReservationInvoice;
use App\Application\Port\Services\PdfGeneratorInterface;
use App\Infrastructure\Pdf\DompdfGenerator;
use App\Presentation\Http\Router;
use App\Presentation\Http\Controller\Api\AuthApiController;
use App\Presentation\Http\Controller\Api\ParkingApiController;
use App\Presentation\Http\Controller\Api\ReservationApiController;

$userRepo = RepositoryFactory::createUserRepository();
$parkingRepo = RepositoryFactory::createParkingRepository();
$reservationRepo = RepositoryFactory::createReservationRepository();
$abonnementRepo = RepositoryFactory::createAbonnementRepository();
$sessionRepo = RepositoryFactory::createParkingSessionRepository();

$passwordHasher = new PasswordHasher();
$jwtEncoder = new JwtEncoder();
$eventDispatcher = new SimpleEventDispatcher();
$pdfGenerator = new DompdfGenerator();

$authController = new AuthApiController(
    new RegisterUser($userRepo, $passwordHasher),
    new LoginUser($userRepo, $passwordHasher, $jwtEncoder),
    new RefreshToken($jwtEncoder),
    new LogoutUser()
);

$parkingController = new ParkingApiController(
    new SearchParkings($parkingRepo),
    new ViewParking($parkingRepo),
    new CreateParking($parkingRepo),
    new EnterParking($parkingRepo, $sessionRepo, $reservationRepo, $abonnementRepo, $userRepo),
    new ExitParking($parkingRepo, $sessionRepo, $reservationRepo, $abonnementRepo),
    new ListUserStationnements($userRepo, $sessionRepo),
    new GetParkingAvailability($parkingRepo)
);

$reservationController = new ReservationApiController(
    new CreateReservation($reservationRepo, $parkingRepo, $userRepo, $eventDispatcher),
    new CancelReservation($reservationRepo, $userRepo),
    new ListUserReservations($userRepo, $reservationRepo),
    new GetReservationInvoice($reservationRepo, $parkingRepo, $pdfGenerator)
);

$router = new Router($authController, $parkingController, $reservationController);
$router->setupApiRoutes();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);

