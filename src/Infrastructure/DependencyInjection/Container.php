<?php declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\RefreshToken;
use App\Application\UseCase\Auth\LogoutUser;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\MySQL\SqlUserRepository;
use App\Infrastructure\Security\JwtEncoder;
use App\Infrastructure\Security\PasswordHasher;
use App\Presentation\Http\Controller\Api\AuthApiController;

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
            PasswordHasherInterface::class => $this->createPasswordHasher(),
            JwtEncoder::class => $this->createJwtEncoder(),
            RegisterUser::class => $this->createRegisterUser(),
            LoginUser::class => $this->createLoginUser(),
            RefreshToken::class => $this->createRefreshToken(),
            LogoutUser::class => $this->createLogoutUser(),
            'App\\Presentation\\Http\\Controller\\Api\\AuthApiController' => $this->createAuthApiController(),
            default => throw new \Exception("Service not found: $id")
        };

        $this->services[$id] = $service;
        return $service;
    }

    private function createUserRepository(): UserRepositoryInterface
    {
        $host = '127.0.0.1';
        $port = $_ENV['APP_DB_PORT'] ?? throw new \RuntimeException('APP_DB_PORT not set in .env');
        $dbname = $_ENV['APP_DB_NAME'] ?? throw new \RuntimeException('APP_DB_NAME not set in .env');
        $user = $_ENV['APP_DB_USER'] ?? throw new \RuntimeException('APP_DB_USER not set in .env');
        $password = $_ENV['APP_DB_PASSWORD'] ?? throw new \RuntimeException('APP_DB_PASSWORD not set in .env');

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        return new SqlUserRepository($pdo);
    }

    private function createPasswordHasher(): PasswordHasherInterface
    {
        return new PasswordHasher();
    }

    private function createJwtEncoder(): JwtEncoder
    {
        return new JwtEncoder();
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
            $this->get(JwtEncoder::class),
            $this->get(PasswordHasherInterface::class)
        );
    }

    private function createRefreshToken(): RefreshToken
    {
        return new RefreshToken(
            $this->get(UserRepositoryInterface::class),
            $this->get(JwtEncoder::class)
        );
    }

    private function createLogoutUser(): LogoutUser
    {
        return new LogoutUser();
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
}