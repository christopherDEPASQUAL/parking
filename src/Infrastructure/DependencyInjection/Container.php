<?php declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Application\Event\Handler\OnReservationCancelled;
use App\Application\Event\Handler\OnReservationCreated;
use App\Application\Port\Messaging\EventDispatcherInterface;
use App\Application\Port\Payments\PaymentGatewayPort;
use App\Application\Port\Security\PasswordHasherInterface;
use App\Application\Port\Security\TokenBlacklistInterface;
use App\Application\Port\Services\JwtEncoderInterface;
use App\Application\UseCase\Abonnements\CreateAbonnement;
use App\Application\UseCase\Abonnements\ListParkingAbonnements;
use App\Application\UseCase\Abonnements\ListUserAbonnements;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\LogoutUser;
use App\Application\UseCase\Auth\RefreshToken;
use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Users\ChangePassword;
use App\Application\UseCase\Users\GetUserProfile;
use App\Application\UseCase\Parkings\CreateParking;
use App\Application\UseCase\Parkings\GetParkingAvailability;
use App\Application\UseCase\Parkings\GetParkingDetails;
use App\Application\UseCase\Parkings\GetParkingMonthlyRevenue;
use App\Application\UseCase\Parkings\ListOverstayedDrivers;
use App\Application\UseCase\Parkings\SearchParkings;
use App\Application\UseCase\Parkings\UpdateParkingCapacity;
use App\Application\UseCase\Parkings\UpdateParkingOpeningHours;
use App\Application\UseCase\Parkings\UpdateParkingTariff;
use App\Application\UseCase\SubscriptionOffers\CreateSubscriptionOffer;
use App\Application\UseCase\SubscriptionOffers\ListParkingSubscriptionOffers;
use App\Application\UseCase\Payments\ProcessPayment;
use App\Application\UseCase\Reservations\CancelReservation;
use App\Application\UseCase\Reservations\CreateReservation;
use App\Application\UseCase\Reservations\ListParkingReservations;
use App\Application\UseCase\Stationnements\EnterParking;
use App\Application\UseCase\Stationnements\ExitParking;
use App\Application\UseCase\Stationnements\ListParkingStationnements;
use App\Application\UseCase\Stationnements\ListUserStationnements;
use App\Application\UseCase\Invoices\GetInvoiceData;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Logging\SimpleLogger;
use App\Infrastructure\Messaging\SimpleEventDispatcher;
use App\Infrastructure\Payments\MockPaymentGateway;
use App\Infrastructure\Persistence\RepositoryFactory;
use App\Infrastructure\Security\FileTokenBlacklist;
use App\Infrastructure\Security\JwtEncoder;
use App\Infrastructure\Security\PasswordHasher;
use App\Presentation\Http\Controller\Api\AbonnementApiController;
use App\Presentation\Http\Controller\Api\AuthApiController;
use App\Presentation\Http\Controller\Api\HealthController;
use App\Presentation\Http\Controller\Api\PaymentApiController;
use App\Presentation\Http\Controller\Api\ParkingApiController;
use App\Presentation\Http\Controller\Api\ReservationApiController;
use App\Presentation\Http\Controller\Api\StationnementApiController;
use App\Presentation\Http\Controller\Api\SubscriptionOfferApiController;
use App\Presentation\Http\Controller\Api\UserApiController;
use App\Presentation\Http\Controller\Web\AbonnementController;
use App\Presentation\Http\Controller\Web\InvoiceController;
use App\Presentation\Http\Controller\Web\ParkingController;
use App\Presentation\Http\Controller\Web\ReservationController;
use App\Presentation\Http\Controller\Web\StationnementController;
use App\Presentation\Http\Middleware\AuthJWTMiddleware;

final class Container
{
    private array $services = [];

    public function get(string $id): object
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        $service = match ($id) {
            UserRepositoryInterface::class => $this->createUserRepository(),
            ParkingRepositoryInterface::class => $this->createParkingRepository(),
            ReservationRepositoryInterface::class => $this->createReservationRepository(),
            AbonnementRepositoryInterface::class => $this->createAbonnementRepository(),
            SubscriptionOfferRepositoryInterface::class => $this->createSubscriptionOfferRepository(),
            StationnementRepositoryInterface::class => $this->createStationnementRepository(),
            PaymentRepositoryInterface::class => $this->createPaymentRepository(),
            PasswordHasherInterface::class => $this->createPasswordHasher(),
            JwtEncoderInterface::class => $this->createJwtEncoder(),
            TokenBlacklistInterface::class => $this->createTokenBlacklist(),
            EventDispatcherInterface::class => $this->createEventDispatcher(),
            SimpleLogger::class => $this->createLogger(),
            PaymentGatewayPort::class => $this->createPaymentGateway(),
            ProcessPayment::class => $this->createProcessPayment(),
            GetInvoiceData::class => $this->createGetInvoiceData(),
            RegisterUser::class => $this->createRegisterUser(),
            LoginUser::class => $this->createLoginUser(),
            RefreshToken::class => $this->createRefreshToken(),
            LogoutUser::class => $this->createLogoutUser(),
            GetUserProfile::class => $this->createGetUserProfile(),
            ChangePassword::class => $this->createChangePassword(),
            CreateParking::class => $this->createCreateParking(),
            SearchParkings::class => $this->createSearchParkings(),
            GetParkingDetails::class => $this->createGetParkingDetails(),
            GetParkingAvailability::class => $this->createGetParkingAvailability(),
            UpdateParkingCapacity::class => $this->createUpdateParkingCapacity(),
            UpdateParkingTariff::class => $this->createUpdateParkingTariff(),
            UpdateParkingOpeningHours::class => $this->createUpdateParkingOpeningHours(),
            GetParkingMonthlyRevenue::class => $this->createGetParkingMonthlyRevenue(),
            ListOverstayedDrivers::class => $this->createListOverstayedDrivers(),
            CreateSubscriptionOffer::class => $this->createCreateSubscriptionOffer(),
            ListParkingSubscriptionOffers::class => $this->createListParkingSubscriptionOffers(),
            CreateReservation::class => $this->createCreateReservation(),
            CancelReservation::class => $this->createCancelReservation(),
            ListParkingReservations::class => $this->createListParkingReservations(),
            CreateAbonnement::class => $this->createCreateAbonnement(),
            ListParkingAbonnements::class => $this->createListParkingAbonnements(),
            ListUserAbonnements::class => $this->createListUserAbonnements(),
            EnterParking::class => $this->createEnterParking(),
            ExitParking::class => $this->createExitParking(),
            ListParkingStationnements::class => $this->createListParkingStationnements(),
            ListUserStationnements::class => $this->createListUserStationnements(),
            AuthJWTMiddleware::class => $this->createAuthJwtMiddleware(),
            AuthApiController::class => $this->createAuthApiController(),
            HealthController::class => $this->createHealthController(),
            ParkingApiController::class => $this->createParkingApiController(),
            ReservationApiController::class => $this->createReservationApiController(),
            AbonnementApiController::class => $this->createAbonnementApiController(),
            StationnementApiController::class => $this->createStationnementApiController(),
            PaymentApiController::class => $this->createPaymentApiController(),
            SubscriptionOfferApiController::class => $this->createSubscriptionOfferApiController(),
            UserApiController::class => $this->createUserApiController(),
            ParkingController::class => $this->createParkingController(),
            ReservationController::class => $this->createReservationController(),
            AbonnementController::class => $this->createAbonnementController(),
            StationnementController::class => $this->createStationnementController(),
            InvoiceController::class => $this->createInvoiceController(),
            default => throw new \Exception("Service not found: $id")
        };

        $this->services[$id] = $service;
        return $service;
    }

    private function createUserRepository(): UserRepositoryInterface
    {
        return RepositoryFactory::createUserRepository();
    }

    private function createParkingRepository(): ParkingRepositoryInterface
    {
        return RepositoryFactory::createParkingRepository();
    }

    private function createReservationRepository(): ReservationRepositoryInterface
    {
        return RepositoryFactory::createReservationRepository();
    }

    private function createAbonnementRepository(): AbonnementRepositoryInterface
    {
        return RepositoryFactory::createAbonnementRepository();
    }

    private function createSubscriptionOfferRepository(): SubscriptionOfferRepositoryInterface
    {
        return RepositoryFactory::createSubscriptionOfferRepository();
    }

    private function createStationnementRepository(): StationnementRepositoryInterface
    {
        return RepositoryFactory::createStationnementRepository();
    }

    private function createPaymentRepository(): PaymentRepositoryInterface
    {
        return RepositoryFactory::createPaymentRepository();
    }

    private function createPasswordHasher(): PasswordHasherInterface
    {
        return new PasswordHasher();
    }

    private function createJwtEncoder(): JwtEncoderInterface
    {
        return new JwtEncoder();
    }

    private function createTokenBlacklist(): TokenBlacklistInterface
    {
        return new FileTokenBlacklist();
    }

    private function createEventDispatcher(): EventDispatcherInterface
    {
        return new SimpleEventDispatcher([
            \App\Domain\Event\ReservationCreated::class => [new OnReservationCreated($this->get(SimpleLogger::class))],
            \App\Domain\Event\ReservationCancelled::class => [new OnReservationCancelled($this->get(SimpleLogger::class))],
        ]);
    }

    private function createLogger(): SimpleLogger
    {
        return new SimpleLogger();
    }

    private function createPaymentGateway(): PaymentGatewayPort
    {
        return new MockPaymentGateway();
    }

    private function createProcessPayment(): ProcessPayment
    {
        return new ProcessPayment(
            $this->get(PaymentGatewayPort::class),
            $this->get(PaymentRepositoryInterface::class)
        );
    }

    private function createGetInvoiceData(): GetInvoiceData
    {
        return new GetInvoiceData(
            $this->get(PaymentRepositoryInterface::class),
            $this->get(ReservationRepositoryInterface::class),
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(StationnementRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class),
            $this->get(SubscriptionOfferRepositoryInterface::class),
            $this->get(UserRepositoryInterface::class)
        );
    }

    private function createRegisterUser(): RegisterUser
    {
        return new RegisterUser(
            $this->get(UserRepositoryInterface::class),
            $this->get(PasswordHasherInterface::class)
        );
    }

    private function createLoginUser(): LoginUser
    {
        return new LoginUser(
            $this->get(UserRepositoryInterface::class),
            $this->get(JwtEncoderInterface::class),
            $this->get(PasswordHasherInterface::class)
        );
    }

    private function createRefreshToken(): RefreshToken
    {
        return new RefreshToken(
            $this->get(UserRepositoryInterface::class),
            $this->get(JwtEncoderInterface::class)
        );
    }

    private function createLogoutUser(): LogoutUser
    {
        return new LogoutUser(
            $this->get(TokenBlacklistInterface::class),
            $this->get(JwtEncoderInterface::class)
        );
    }

    private function createGetUserProfile(): GetUserProfile
    {
        return new GetUserProfile($this->get(UserRepositoryInterface::class));
    }

    private function createChangePassword(): ChangePassword
    {
        return new ChangePassword(
            $this->get(UserRepositoryInterface::class),
            $this->get(PasswordHasherInterface::class)
        );
    }

    private function createCreateParking(): CreateParking
    {
        return new CreateParking($this->get(ParkingRepositoryInterface::class));
    }

    private function createSearchParkings(): SearchParkings
    {
        return new SearchParkings($this->get(ParkingRepositoryInterface::class));
    }

    private function createGetParkingDetails(): GetParkingDetails
    {
        return new GetParkingDetails($this->get(ParkingRepositoryInterface::class));
    }

    private function createGetParkingAvailability(): GetParkingAvailability
    {
        return new GetParkingAvailability($this->get(ParkingRepositoryInterface::class));
    }

    private function createUpdateParkingCapacity(): UpdateParkingCapacity
    {
        return new UpdateParkingCapacity($this->get(ParkingRepositoryInterface::class));
    }

    private function createUpdateParkingTariff(): UpdateParkingTariff
    {
        return new UpdateParkingTariff($this->get(ParkingRepositoryInterface::class));
    }

    private function createUpdateParkingOpeningHours(): UpdateParkingOpeningHours
    {
        return new UpdateParkingOpeningHours($this->get(ParkingRepositoryInterface::class));
    }

    private function createGetParkingMonthlyRevenue(): GetParkingMonthlyRevenue
    {
        return new GetParkingMonthlyRevenue($this->get(ParkingRepositoryInterface::class));
    }

    private function createCreateSubscriptionOffer(): CreateSubscriptionOffer
    {
        return new CreateSubscriptionOffer(
            $this->get(SubscriptionOfferRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class)
        );
    }

    private function createListParkingSubscriptionOffers(): ListParkingSubscriptionOffers
    {
        return new ListParkingSubscriptionOffers($this->get(SubscriptionOfferRepositoryInterface::class));
    }

    private function createListOverstayedDrivers(): ListOverstayedDrivers
    {
        return new ListOverstayedDrivers(
            $this->get(StationnementRepositoryInterface::class),
            $this->get(ReservationRepositoryInterface::class),
            $this->get(AbonnementRepositoryInterface::class)
        );
    }

    private function createCreateReservation(): CreateReservation
    {
        return new CreateReservation(
            $this->get(ReservationRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class),
            $this->get(UserRepositoryInterface::class),
            $this->get(EventDispatcherInterface::class),
            $this->get(ProcessPayment::class)
        );
    }

    private function createCancelReservation(): CancelReservation
    {
        return new CancelReservation(
            $this->get(ReservationRepositoryInterface::class),
            $this->get(UserRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class),
            $this->get(EventDispatcherInterface::class)
        );
    }

    private function createListParkingReservations(): ListParkingReservations
    {
        return new ListParkingReservations($this->get(ReservationRepositoryInterface::class));
    }

    private function createCreateAbonnement(): CreateAbonnement
    {
        return new CreateAbonnement(
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class),
            $this->get(UserRepositoryInterface::class),
            $this->get(SubscriptionOfferRepositoryInterface::class),
            $this->get(ProcessPayment::class)
        );
    }

    private function createListParkingAbonnements(): ListParkingAbonnements
    {
        return new ListParkingAbonnements(
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(SubscriptionOfferRepositoryInterface::class)
        );
    }

    private function createListUserAbonnements(): ListUserAbonnements
    {
        return new ListUserAbonnements(
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(SubscriptionOfferRepositoryInterface::class)
        );
    }

    private function createEnterParking(): EnterParking
    {
        return new EnterParking(
            $this->get(ParkingRepositoryInterface::class),
            $this->get(ReservationRepositoryInterface::class),
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(StationnementRepositoryInterface::class),
            $this->get(UserRepositoryInterface::class)
        );
    }

    private function createExitParking(): ExitParking
    {
        return new ExitParking(
            $this->get(ParkingRepositoryInterface::class),
            $this->get(ReservationRepositoryInterface::class),
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(StationnementRepositoryInterface::class),
            $this->get(ProcessPayment::class)
        );
    }

    private function createListParkingStationnements(): ListParkingStationnements
    {
        return new ListParkingStationnements($this->get(StationnementRepositoryInterface::class));
    }

    private function createListUserStationnements(): ListUserStationnements
    {
        return new ListUserStationnements($this->get(StationnementRepositoryInterface::class));
    }

    private function createAuthJwtMiddleware(): AuthJWTMiddleware
    {
        return new AuthJWTMiddleware(
            $this->get(JwtEncoderInterface::class),
            $this->get(TokenBlacklistInterface::class)
        );
    }

    private function createAuthApiController(): AuthApiController
    {
        return new AuthApiController(
            $this->get(RegisterUser::class),
            $this->get(LoginUser::class),
            $this->get(RefreshToken::class),
            $this->get(LogoutUser::class)
        );
    }

    private function createHealthController(): HealthController
    {
        return new HealthController();
    }

    private function createParkingApiController(): ParkingApiController
    {
        return new ParkingApiController(
            $this->get(CreateParking::class),
            $this->get(SearchParkings::class),
            $this->get(GetParkingDetails::class),
            $this->get(GetParkingAvailability::class),
            $this->get(UpdateParkingCapacity::class),
            $this->get(UpdateParkingTariff::class),
            $this->get(UpdateParkingOpeningHours::class),
            $this->get(GetParkingMonthlyRevenue::class),
            $this->get(ListOverstayedDrivers::class),
            $this->get(ParkingRepositoryInterface::class)
        );
    }

    private function createReservationApiController(): ReservationApiController
    {
        return new ReservationApiController(
            $this->get(CreateReservation::class),
            $this->get(CancelReservation::class),
            $this->get(ListParkingReservations::class),
            $this->get(ReservationRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class)
        );
    }

    private function createAbonnementApiController(): AbonnementApiController
    {
        return new AbonnementApiController(
            $this->get(CreateAbonnement::class),
            $this->get(ListParkingAbonnements::class),
            $this->get(ListUserAbonnements::class),
            $this->get(ParkingRepositoryInterface::class)
        );
    }

    private function createStationnementApiController(): StationnementApiController
    {
        return new StationnementApiController(
            $this->get(EnterParking::class),
            $this->get(ExitParking::class),
            $this->get(ListParkingStationnements::class),
            $this->get(ListUserStationnements::class),
            $this->get(StationnementRepositoryInterface::class),
            $this->get(ParkingRepositoryInterface::class)
        );
    }

    private function createPaymentApiController(): PaymentApiController
    {
        return new PaymentApiController(
            $this->get(ProcessPayment::class),
            $this->get(ReservationRepositoryInterface::class),
            $this->get(AbonnementRepositoryInterface::class),
            $this->get(StationnementRepositoryInterface::class),
            $this->get(SubscriptionOfferRepositoryInterface::class)
        );
    }

    private function createSubscriptionOfferApiController(): SubscriptionOfferApiController
    {
        return new SubscriptionOfferApiController(
            $this->get(CreateSubscriptionOffer::class),
            $this->get(ListParkingSubscriptionOffers::class),
            $this->get(ParkingRepositoryInterface::class)
        );
    }

    private function createUserApiController(): UserApiController
    {
        return new UserApiController(
            $this->get(GetUserProfile::class),
            $this->get(ChangePassword::class)
        );
    }

    private function createParkingController(): ParkingController
    {
        return new ParkingController(
            $this->get(SearchParkings::class),
            $this->get(GetParkingDetails::class)
        );
    }

    private function createReservationController(): ReservationController
    {
        return new ReservationController(
            $this->get(ListParkingReservations::class)
        );
    }

    private function createAbonnementController(): AbonnementController
    {
        return new AbonnementController(
            $this->get(ListParkingAbonnements::class),
            $this->get(ListUserAbonnements::class)
        );
    }

    private function createStationnementController(): StationnementController
    {
        return new StationnementController(
            $this->get(ListParkingStationnements::class),
            $this->get(ListUserStationnements::class)
        );
    }

    private function createInvoiceController(): InvoiceController
    {
        return new InvoiceController(
            $this->get(GetInvoiceData::class)
        );
    }
}
