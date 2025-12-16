<?php declare(strict_types=1);

namespace App\Application\Port\Services;

/**
 * Port : encode/valide des JWT (sans dépendance HTTP).
 */
interface JwtEncoderInterface
{
    public function generateAccessToken(string $userId, string $email, string $role): string;

    public function generateRefreshToken(string $userId): string;

    /**
     * @return object Décodage (claims) ou exception si invalide.
     */
    public function validateToken(string $token): object;
}
