<?php declare(strict_types=1);

namespace App\Application\DTO\Auth;

use App\Domain\Enum\UserRole;

final readonly class RegisterUserRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public UserRole $role
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'] ?? throw new \InvalidArgumentException('Email is required'),
            $data['password'] ?? throw new \InvalidArgumentException('Password is required'),
            $data['first_name'] ?? throw new \InvalidArgumentException('First name is required'),
            $data['last_name'] ?? throw new \InvalidArgumentException('Last name is required'),
            UserRole::from($data['role'] ?? 'client')
        );
    }
}