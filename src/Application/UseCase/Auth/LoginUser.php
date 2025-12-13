<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\JwtEncoder;

final class LoginUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtEncoder $jwtEncoder
    ) {}

    public function execute(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new \InvalidArgumentException("Invalid credentials");
        }

        if (!$user->verifyPassword($password)) {
            throw new \InvalidArgumentException("Invalid credentials");
        }

        $accessToken = $this->jwtEncoder->generateAccessToken(
            $user->getId(),
            $user->getEmail(),
            $user->getRole()->value
        );

        $refreshToken = $this->jwtEncoder->generateRefreshToken($user->getId());

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $user
        ];
    }
}