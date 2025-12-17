<?php

declare(strict_types=1);

namespace App\Application\UseCase\Payments;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\DTO\Payments\PaymentResult;
use App\Application\Port\Payments\PaymentGatewayPort;

final class ChargePayment
{
    public function __construct(
        private PaymentGatewayPort $paymentGateway
    ) {}

    public function execute(ChargeRequest $request): PaymentResult
    {
        return $this->paymentGateway->charge($request);
    }
}
