<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\RefreshToken;
use App\Application\UseCase\Auth\LogoutUser;
use App\Application\DTO\Auth\RegisterUserRequest;
use App\Application\DTO\Auth\RegisterUserResponse;
use App\Application\DTO\Auth\LoginUserRequest;
use App\Application\DTO\Auth\LoginUserResponse;

final class AuthApiController
{
    public function __construct(
        private readonly RegisterUser $registerUser,
        private readonly LoginUser $loginUser,
        private readonly RefreshToken $refreshToken,
        private readonly LogoutUser $logoutUser
    ) {}

    public function register(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = RegisterUserRequest::fromArray($data);

            $user = $this->registerUser->execute(
                $request->email,
                $request->password,
                $request->firstName,
                $request->lastName,
                $request->role
            );

            $response = new RegisterUserResponse($user);

            http_response_code(201);
            echo json_encode($response->toArray());
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function login(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = LoginUserRequest::fromArray($data);

            $result = $this->loginUser->execute(
                $request->email,
                $request->password
            );

            $response = new LoginUserResponse(
                $result['access_token'],
                $result['refresh_token'],
                $result['user']
            );

            http_response_code(200);
            echo json_encode($response->toArray());
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function refresh(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $result = $this->refreshToken->execute($data['refresh_token']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'access_token' => $result['access_token']
            ]);
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function logout(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'] ?? null;
            if ($token === null && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/^Bearer\\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    $token = trim($matches[1]);
                }
            }

            if ($token === null) {
                throw new \InvalidArgumentException('Token is required.');
            }

            $this->logoutUser->execute($token);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
