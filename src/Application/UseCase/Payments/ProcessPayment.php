<?php declare(strict_types=1);

namespace App\Application\UseCase\Payments;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\Port\Payments\PaymentGatewayPort;
use App\Domain\Entity\Payment;
use App\Domain\Enum\PaymentStatus;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;

final class ProcessPayment
{
    public function __construct(
        private readonly PaymentGatewayPort $paymentGateway,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {}

    public function execute(ChargeRequest $request): Payment
    {
        $result = $this->paymentGateway->charge($request);

        $status = match ($result->status) {
            'approved' => PaymentStatus::APPROVED,
            'refused' => PaymentStatus::REFUSED,
            default => PaymentStatus::PENDING,
        };

        $payment = new Payment(
            PaymentId::generate(),
            UserId::fromString($request->userId),
            Money::fromCents($request->amountCents, $request->currency),
            $status,
            $request->reservationId ? ReservationId::fromString($request->reservationId) : null,
            $request->abonnementId ? AbonnementId::fromString($request->abonnementId) : null,
            $request->stationnementId ? StationnementId::fromString($request->stationnementId) : null,
            $result->transactionId
        );

        $this->paymentRepository->save($payment);

        return $payment;
    }
}
