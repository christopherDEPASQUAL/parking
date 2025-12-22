<?php declare(strict_types=1);

namespace App\Application\DTO\Auth;

use App\Domain\Entity\User;

final readonly class LoginUserResponse
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public User $user
    ) {}

    public function toArray(): array
    {
        return [
            'success' => true,
            'token' => $this->accessToken,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'user' => [
                'id' => (string) $this->user->getId(),
                'email' => (string) $this->user->getEmail(),
                'first_name' => $this->user->getFirstName(),
                'last_name' => $this->user->getLastName(),
                'role' => $this->user->getRole()->value
            ]
        ];
    }
}
