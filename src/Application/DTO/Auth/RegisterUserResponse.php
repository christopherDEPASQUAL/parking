<?php declare(strict_types=1);

namespace App\Application\DTO\Auth;

use App\Domain\Entity\User;

final readonly class RegisterUserResponse
{
    public function __construct(
        public User $user
    ) {}

    public function toArray(): array
    {
        return [
            'success' => true,
            'message' => 'User registered successfully',
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