<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\JwtEncoder;

final class RefreshToken
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtEncoder $jwtEncoder
    ) {}

    public function execute(string $refreshToken): array
    {
        try {
            $decoded = $this->jwtEncoder->validateToken($refreshToken);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid refresh token");
        }

        if (!isset($decoded->type) || $decoded->type !== 'refresh') {
            throw new \InvalidArgumentException("Invalid token type");
        }

        $user = $this->userRepository->findById($decoded->user_id);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        $accessToken = $this->jwtEncoder->generateAccessToken(
            $user->getId(),
            $user->getEmail(),
            $user->getRole()->value
        );

        return [
            'access_token' => $accessToken
        ];
    }
}