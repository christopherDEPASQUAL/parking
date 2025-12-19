<?php

declare(strict_types=1);

namespace App\Application\Port\Payments;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\DTO\Payments\PaymentResult;

interface PaymentGatewayPort
{
    public function charge(ChargeRequest $request): PaymentResult;
}
