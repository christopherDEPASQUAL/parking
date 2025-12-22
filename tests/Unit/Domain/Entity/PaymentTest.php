<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Payment;
use App\Domain\Enum\PaymentStatus;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class PaymentTest extends TestCase
{
    public function testCreatePaymentWithReservation(): void
    {
        $payment = new Payment(
            PaymentId::fromString('11111111-1111-4111-8111-111111111111'),
            UserId::fromString('22222222-2222-4222-8222-222222222222'),
            Money::fromCents(1500, 'EUR'),
            PaymentStatus::APPROVED,
            ReservationId::fromString('33333333-3333-4333-8333-333333333333')
        );

        self::assertSame(PaymentStatus::APPROVED, $payment->status());
        self::assertSame(1500, $payment->amount()->getAmountInCents());
        self::assertNotNull($payment->reservationId());
    }

    public function testPaymentMustTargetExactlyOneReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Payment(
            PaymentId::fromString('11111111-1111-4111-8111-111111111111'),
            UserId::fromString('22222222-2222-4222-8222-222222222222'),
            Money::fromCents(1500, 'EUR'),
            PaymentStatus::APPROVED
        );
    }

    public function testPaymentRejectsMultipleReferences(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Payment(
            PaymentId::fromString('11111111-1111-4111-8111-111111111111'),
            UserId::fromString('22222222-2222-4222-8222-222222222222'),
            Money::fromCents(1500, 'EUR'),
            PaymentStatus::APPROVED,
            ReservationId::fromString('33333333-3333-4333-8333-333333333333'),
            AbonnementId::fromString('44444444-4444-4444-8444-444444444444'),
            StationnementId::fromString('55555555-5555-4555-8555-555555555555')
        );
    }

    public function testPaymentWithStationnement(): void
    {
        $payment = new Payment(
            PaymentId::fromString('11111111-1111-4111-8111-111111111111'),
            UserId::fromString('22222222-2222-4222-8222-222222222222'),
            Money::fromCents(2000, 'EUR'),
            PaymentStatus::PENDING,
            null,
            null,
            StationnementId::fromString('55555555-5555-4555-8555-555555555555'),
            'provider-ref'
        );

        self::assertSame('provider-ref', $payment->providerReference());
        self::assertNotNull($payment->stationnementId());
    }

    public function testPaymentWithAbonnementExposesGetters(): void
    {
        $paymentId = PaymentId::fromString('11111111-1111-4111-8111-111111111111');
        $userId = UserId::fromString('22222222-2222-4222-8222-222222222222');
        $abonnementId = AbonnementId::fromString('44444444-4444-4444-8444-444444444444');

        $payment = new Payment(
            $paymentId,
            $userId,
            Money::fromCents(5000, 'EUR'),
            PaymentStatus::REFUSED,
            null,
            $abonnementId
        );

        self::assertSame($paymentId, $payment->id());
        self::assertSame($userId, $payment->userId());
        self::assertSame($abonnementId, $payment->abonnementId());
        self::assertSame(PaymentStatus::REFUSED, $payment->status());
        self::assertNotNull($payment->createdAt());
    }
}
