<?php declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Application\Port\Services\JwtEncoderInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;

final class LoginUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtEncoderInterface $jwtEncoder,
        private readonly PasswordHasherInterface $passwordHasher
    ) {}

    public function execute(string $email, string $password): array
    {
        $emailVO = Email::fromString($email);
        $user = $this->userRepository->findByEmail($emailVO);

        if ($user === null) {
            throw new \InvalidArgumentException("Invalid credentials");
        }

        if (!$this->passwordHasher->verify($password, $user->getPasswordHash())) {
            throw new \InvalidArgumentException("Invalid credentials");
        }

        $accessToken = $this->jwtEncoder->generateAccessToken(
            (string) $user->getId(),
            (string) $user->getEmail(),
            $user->getRole()->value
        );

        $refreshToken = $this->jwtEncoder->generateRefreshToken((string) $user->getId());

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $user
        ];
    }
}
