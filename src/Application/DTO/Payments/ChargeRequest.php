<?php declare(strict_types=1);

namespace App\Application\DTO\Payments;

final class ChargeRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly int $amountCents,
        public readonly string $currency = 'EUR',
        public readonly ?string $reservationId = null,
        public readonly ?string $abonnementId = null,
        public readonly ?string $stationnementId = null,
        public readonly array $metadata = []
    ) {}
}
