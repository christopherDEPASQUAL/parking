<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;
use App\Application\Port\Services\JwtEncoderInterface;

final class RefreshToken
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtEncoderInterface $jwtEncoder
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

        $userId = UserId::fromString($decoded->user_id);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        $accessToken = $this->jwtEncoder->generateAccessToken(
            (string) $user->getId(),
            (string) $user->getEmail(),
            $user->getRole()->value
        );

        return [
            'access_token' => $accessToken
        ];
    }
}
