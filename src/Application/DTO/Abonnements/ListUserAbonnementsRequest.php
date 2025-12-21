<?php declare(strict_types=1);

namespace App\Application\DTO\Abonnements;

final class ListUserAbonnementsRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $status = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['user_id'] ?? throw new \InvalidArgumentException('user_id is required'),
            $data['status'] ?? null
        );
    }
}
