<?php declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\Services\JwtEncoderInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtEncoder implements JwtEncoderInterface
{
    private string $secretKey;
    private const ALGORITHM = 'HS256';
    private const ACCESS_TOKEN_EXPIRATION = 3600;
    private const REFRESH_TOKEN_EXPIRATION = 2592000;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? ($_ENV['JWT_SECRET_KEY'] ?? bin2hex(random_bytes(32)));
    }

    public function generateAccessToken(string $userId, string $email, string $role): string
    {
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + self::ACCESS_TOKEN_EXPIRATION
        ];

        return JWT::encode($payload, $this->secretKey, self::ALGORITHM);
    }

    public function generateRefreshToken(string $userId): string
    {
        $payload = [
            'user_id' => $userId,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + self::REFRESH_TOKEN_EXPIRATION
        ];

        return JWT::encode($payload, $this->secretKey, self::ALGORITHM);
    }

    public function validateToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secretKey, self::ALGORITHM));
    }
}
