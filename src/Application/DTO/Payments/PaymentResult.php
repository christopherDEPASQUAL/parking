<?php declare(strict_types=1);

namespace App\Application\DTO\Payments;

final class PaymentResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $transactionId = null,
        public readonly ?string $refusalReason = null
    ) {}

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
