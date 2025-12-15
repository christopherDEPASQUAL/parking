<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\PasswordHash;
use App\Infrastructure\Security\JwtEncoder;

final class LoginUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtEncoder $jwtEncoder,
        private readonly PasswordHasherInterface $passwordHasher
    ) {}

    public function execute(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new \InvalidArgumentException("Invalid credentials");
        }

        $storedPasswordHash = PasswordHash::fromHash($user->getPasswordHash());

        if (!$this->passwordHasher->verify($password, $storedPasswordHash)) {
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