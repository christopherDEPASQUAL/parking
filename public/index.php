<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

use App\Infrastructure\DependencyInjection\Container;
use App\Infrastructure\Http\Router;
use App\Presentation\Http\Middleware\AuthJWTMiddleware;
use App\Presentation\Http\Controller\Api\AuthApiController;
use App\Presentation\Http\Controller\Api\HealthController;
use App\Presentation\Http\Controller\Api\PaymentApiController;
use App\Presentation\Http\Controller\Api\ParkingApiController;
use App\Presentation\Http\Controller\Api\ReservationApiController;
use App\Presentation\Http\Controller\Api\AbonnementApiController;
use App\Presentation\Http\Controller\Api\StationnementApiController;
use App\Presentation\Http\Controller\Api\SubscriptionOfferApiController;
use App\Presentation\Http\Controller\Api\UserApiController;
use App\Presentation\Http\Controller\Web\ParkingController;
use App\Presentation\Http\Controller\Web\ReservationController;
use App\Presentation\Http\Controller\Web\AbonnementController;
use App\Presentation\Http\Controller\Web\StationnementController;
use App\Presentation\Http\Controller\Web\InvoiceController;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $container = new Container();
    $router = new Router($container);

    $authMiddleware = [AuthJWTMiddleware::class];

    $router->addRoute('GET', '/', HealthController::class, 'ping');

    $router->addRoute('POST', '/auth/register', AuthApiController::class, 'register');
    $router->addRoute('POST', '/auth/login', AuthApiController::class, 'login');
    $router->addRoute('POST', '/auth/refresh', AuthApiController::class, 'refresh');
    $router->addRoute('POST', '/auth/logout', AuthApiController::class, 'logout', $authMiddleware);
    $router->addRoute('GET', '/auth/me', UserApiController::class, 'me', $authMiddleware);

    $router->addRoute('GET', '/parkings/search', ParkingApiController::class, 'search', $authMiddleware);
    $router->addRoute('GET', '/parkings/{id}', ParkingApiController::class, 'detailsById', $authMiddleware);
    $router->addRoute('GET', '/parkings/{id}/availability', ParkingApiController::class, 'availability', $authMiddleware);
    $router->addRoute('GET', '/parkings/{id}/subscription-offers', SubscriptionOfferApiController::class, 'listByParking', $authMiddleware);

    $router->addRoute('POST', '/reservations', ReservationApiController::class, 'createFromAuth', $authMiddleware);
    $router->addRoute('GET', '/reservations/me', ReservationApiController::class, 'listMine', $authMiddleware);
    $router->addRoute('GET', '/reservations/{id}', ReservationApiController::class, 'details', $authMiddleware);
    $router->addRoute('POST', '/reservations/{id}/cancel', ReservationApiController::class, 'cancelFromAuth', $authMiddleware);

    $router->addRoute('POST', '/stationings/enter', StationnementApiController::class, 'enterFromAuth', $authMiddleware);
    $router->addRoute('POST', '/stationings/exit', StationnementApiController::class, 'exitFromAuth', $authMiddleware);
    $router->addRoute('GET', '/stationings/me', StationnementApiController::class, 'listMine', $authMiddleware);

    $router->addRoute('GET', '/invoices/reservations/{reservation_id}', InvoiceController::class, 'show', $authMiddleware);

    $router->addRoute('GET', '/owner/parkings', ParkingApiController::class, 'listOwner', $authMiddleware);
    $router->addRoute('POST', '/owner/parkings', ParkingApiController::class, 'create', $authMiddleware);
    $router->addRoute('PATCH', '/owner/parkings/{id}', ParkingApiController::class, 'updateDetails', $authMiddleware);
    $router->addRoute('GET', '/owner/parkings/{id}/opening-hours', ParkingApiController::class, 'getOpeningHours', $authMiddleware);
    $router->addRoute('PATCH', '/owner/parkings/{id}/opening-hours', ParkingApiController::class, 'updateOpeningHours', $authMiddleware);
    $router->addRoute('GET', '/owner/parkings/{id}/pricing-plan', ParkingApiController::class, 'getPricingPlan', $authMiddleware);
    $router->addRoute('PATCH', '/owner/parkings/{id}/pricing-plan', ParkingApiController::class, 'updatePricingPlan', $authMiddleware);
    $router->addRoute('GET', '/owner/parkings/{id}/reservations', ReservationApiController::class, 'listByParking', $authMiddleware);
    $router->addRoute('GET', '/owner/parkings/{id}/stationings', StationnementApiController::class, 'listByParking', $authMiddleware);
    $router->addRoute('GET', '/owner/parkings/{id}/revenue', ParkingApiController::class, 'monthlyRevenue', $authMiddleware);
    $router->addRoute('GET', '/owner/parkings/{id}/overstayers', ParkingApiController::class, 'overstayedDrivers', $authMiddleware);
    $router->addRoute('POST', '/owner/parkings/{id}/subscription-offers', SubscriptionOfferApiController::class, 'create', $authMiddleware);

    $router->addRoute('POST', '/api/auth/register', AuthApiController::class, 'register');
    $router->addRoute('POST', '/api/auth/login', AuthApiController::class, 'login');
    $router->addRoute('POST', '/api/auth/refresh', AuthApiController::class, 'refresh');
    $router->addRoute('POST', '/api/auth/logout', AuthApiController::class, 'logout', $authMiddleware);

    $router->addRoute('GET', '/api/users/me', UserApiController::class, 'me', $authMiddleware);
    $router->addRoute('POST', '/api/users/change-password', UserApiController::class, 'changePassword', $authMiddleware);

    $router->addRoute('POST', '/api/parkings', ParkingApiController::class, 'create', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/search', ParkingApiController::class, 'search', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/details', ParkingApiController::class, 'details', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/availability', ParkingApiController::class, 'availability', $authMiddleware);
    $router->addRoute('PUT', '/api/parkings/capacity', ParkingApiController::class, 'updateCapacity', $authMiddleware);
    $router->addRoute('PUT', '/api/parkings/tariff', ParkingApiController::class, 'updateTariff', $authMiddleware);
    $router->addRoute('PUT', '/api/parkings/opening-hours', ParkingApiController::class, 'updateOpeningHours', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/monthly-revenue', ParkingApiController::class, 'monthlyRevenue', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/subscription-offers', SubscriptionOfferApiController::class, 'listByParking', $authMiddleware);
    $router->addRoute('POST', '/api/parkings/subscription-offers', SubscriptionOfferApiController::class, 'create', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/subscription-plans', SubscriptionOfferApiController::class, 'listByParking', $authMiddleware);
    $router->addRoute('GET', '/api/parkings/overstayed', ParkingApiController::class, 'overstayedDrivers', $authMiddleware);

    $router->addRoute('POST', '/api/reservations', ReservationApiController::class, 'create', $authMiddleware);
    $router->addRoute('POST', '/api/reservations/cancel', ReservationApiController::class, 'cancel', $authMiddleware);
    $router->addRoute('GET', '/api/reservations/parking', ReservationApiController::class, 'listByParking', $authMiddleware);

    $router->addRoute('POST', '/api/abonnements', AbonnementApiController::class, 'create', $authMiddleware);
    $router->addRoute('GET', '/api/abonnements/parking', AbonnementApiController::class, 'listByParking', $authMiddleware);
    $router->addRoute('GET', '/api/abonnements/user', AbonnementApiController::class, 'listByUser', $authMiddleware);

    $router->addRoute('POST', '/api/stationnements/enter', StationnementApiController::class, 'enter', $authMiddleware);
    $router->addRoute('POST', '/api/stationnements/exit', StationnementApiController::class, 'exit', $authMiddleware);
    $router->addRoute('GET', '/api/stationnements/parking', StationnementApiController::class, 'listByParking', $authMiddleware);
    $router->addRoute('GET', '/api/stationnements/user', StationnementApiController::class, 'listByUser', $authMiddleware);
    $router->addRoute('POST', '/api/payments/charge', PaymentApiController::class, 'charge', $authMiddleware);
    $router->addRoute('GET', '/api/invoices', InvoiceController::class, 'show', $authMiddleware);

    $router->addRoute('GET', '/web/parkings/search', ParkingController::class, 'search');
    $router->addRoute('GET', '/web/parkings/details', ParkingController::class, 'details');
    $router->addRoute('GET', '/web/reservations/parking', ReservationController::class, 'listByParking');
    $router->addRoute('GET', '/web/abonnements/parking', AbonnementController::class, 'listByParking');
    $router->addRoute('GET', '/web/abonnements/user', AbonnementController::class, 'listByUser');
    $router->addRoute('GET', '/web/stationnements/parking', StationnementController::class, 'listByParking');
    $router->addRoute('GET', '/web/stationnements/user', StationnementController::class, 'listByUser');
    $router->addRoute('GET', '/web/invoices', InvoiceController::class, 'show');

    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $router->dispatch($method, $uri);

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
