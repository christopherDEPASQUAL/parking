<?php declare(strict_types=1);

namespace App\Application\DTO\Invoices;

final class GetInvoiceRequest
{
    public function __construct(
        public readonly ?string $reservationId = null,
        public readonly ?string $abonnementId = null,
        public readonly ?string $stationnementId = null,
        public readonly ?string $paymentId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['reservation_id'] ?? null,
            $data['abonnement_id'] ?? null,
            $data['stationnement_id'] ?? null,
            $data['payment_id'] ?? null
        );
    }
}
