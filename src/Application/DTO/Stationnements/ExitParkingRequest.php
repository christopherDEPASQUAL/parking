<?php declare(strict_types=1);

namespace App\Application\DTO\Stationnements;

final class ExitParkingRequest
{
    public function __construct(
        public readonly string $sessionId,
        public readonly ?\DateTimeImmutable $at = null
    ) {}

    public static function fromArray(array $data): self
    {
        $at = isset($data['at']) ? new \DateTimeImmutable($data['at']) : null;

        return new self(
            $data['session_id'] ?? throw new \InvalidArgumentException('session_id is required'),
            $at
        );
    }
}
