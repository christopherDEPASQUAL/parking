<?php declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\DTO\Payments\PaymentResult;
use App\Application\Port\Payments\PaymentGatewayPort;

final class MockPaymentGateway implements PaymentGatewayPort
{
    public function charge(ChargeRequest $request): PaymentResult
    {
        return new PaymentResult('approved', 'mock-' . bin2hex(random_bytes(6)));
    }
}
