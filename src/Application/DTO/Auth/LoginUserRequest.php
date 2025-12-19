<?php declare(strict_types=1);

namespace App\Application\DTO\Auth;

final readonly class LoginUserRequest
{
    public function __construct(
        public string $email,
        public string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'] ?? throw new \InvalidArgumentException('Email is required'),
            $data['password'] ?? throw new \InvalidArgumentException('Password is required')
        );
    }
}