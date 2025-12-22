<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Security\TokenBlacklistInterface;
use App\Application\Port\Services\JwtEncoderInterface;

final class LogoutUser
{
    public function __construct(
        private readonly TokenBlacklistInterface $tokenBlacklist,
        private readonly JwtEncoderInterface $jwtEncoder
    ) {}

    public function execute(string $token): void
    {
        $decoded = $this->jwtEncoder->validateToken($token);

        if (!isset($decoded->exp)) {
            throw new \InvalidArgumentException('Token missing exp claim.');
        }

        $this->tokenBlacklist->revoke($token, (int) $decoded->exp);
    }
}
