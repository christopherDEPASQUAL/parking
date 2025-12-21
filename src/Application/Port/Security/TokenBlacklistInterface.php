<?php declare(strict_types=1);

namespace App\Application\Port\Security;

interface TokenBlacklistInterface
{
    public function revoke(string $token, int $expiresAt): void;

    public function isRevoked(string $token): bool;
}
