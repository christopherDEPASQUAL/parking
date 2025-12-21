<?php declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Application\Port\Security\TokenBlacklistInterface;
use App\Application\Port\Services\JwtEncoderInterface;

final class AuthJWTMiddleware
{
    public function __construct(
        private readonly JwtEncoderInterface $jwtEncoder,
        private readonly TokenBlacklistInterface $tokenBlacklist
    ) {}

    public function handle(callable $next): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.*)$/i', $header, $matches)) {
            $this->unauthorized('Missing Authorization header.');
            return;
        }

        $token = trim($matches[1]);
        if ($token === '') {
            $this->unauthorized('Missing token.');
            return;
        }

        if ($this->tokenBlacklist->isRevoked($token)) {
            $this->unauthorized('Token revoked.');
            return;
        }

        try {
            $claims = $this->jwtEncoder->validateToken($token);
        } catch (\Throwable $e) {
            $this->unauthorized('Invalid token.');
            return;
        }

        if (isset($claims->type) && $claims->type === 'refresh') {
            $this->unauthorized('Refresh token cannot access protected routes.');
            return;
        }

        $_SERVER['AUTH_USER_ID'] = $claims->user_id ?? null;
        $_SERVER['AUTH_USER_ROLE'] = $claims->role ?? null;
        $_SERVER['AUTH_USER_EMAIL'] = $claims->email ?? null;

        $next();
    }

    private function unauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
    }
}
