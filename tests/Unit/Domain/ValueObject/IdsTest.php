<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\SubscriptionOfferId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class IdsTest extends TestCase
{
    public function testGenerateAndFromString(): void
    {
        $userId = UserId::generate();
        self::assertTrue($userId->equals(UserId::fromString($userId->getValue())));

        $reservationId = ReservationId::generate();
        self::assertTrue($reservationId->equals(ReservationId::fromString($reservationId->getValue())));
        self::assertSame($reservationId->getValue(), $reservationId->value());
        self::assertSame($reservationId->getValue(), (string) $reservationId);
        self::assertSame($reservationId->getValue(), $reservationId->jsonSerialize());
        self::assertSame(['reservationId' => $reservationId->getValue()], $reservationId->toArray());

        $paymentId = PaymentId::generate();
        self::assertTrue($paymentId->equals(PaymentId::fromString($paymentId->getValue())));
        self::assertSame($paymentId->getValue(), (string) $paymentId);

        $abonnementId = AbonnementId::generate();
        self::assertTrue($abonnementId->equals(AbonnementId::fromString($abonnementId->getValue())));
        self::assertSame($abonnementId->getValue(), (string) $abonnementId);

        $stationnementId = StationnementId::generate();
        self::assertTrue($stationnementId->equals(StationnementId::fromString($stationnementId->getValue())));
        self::assertSame($stationnementId->getValue(), (string) $stationnementId);

        $offerId = SubscriptionOfferId::generate();
        self::assertTrue($offerId->equals(SubscriptionOfferId::fromString($offerId->getValue())));
        self::assertSame($offerId->getValue(), (string) $offerId);
    }

    public function testParkingSpotIdFromString(): void
    {
        $id = ParkingSpotId::fromString('11111111-1111-4111-8111-111111111111');

        self::assertSame('11111111-1111-4111-8111-111111111111', $id->getValue());
    }

    public function testParkingIdRejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ParkingId::fromString('');
    }

    public function testParkingIdGenerateAndEquals(): void
    {
        $id = ParkingId::generate();

        self::assertSame($id->getValue(), $id->value());
        self::assertSame($id->getValue(), (string) $id);
        self::assertTrue($id->equals(ParkingId::fromString($id->getValue())));
    }

    public function testInvalidParkingSpotIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ParkingSpotId::fromString('not-a-uuid');
    }

    public function testUserIdToString(): void
    {
        $userId = UserId::generate();

        self::assertSame($userId->getValue(), (string) $userId);
    }

    public function testInvalidUserIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserId::fromString('not-a-uuid');
    }

    public function testInvalidPaymentIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PaymentId::fromString('not-a-uuid');
    }

    public function testInvalidSubscriptionOfferIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SubscriptionOfferId::fromString('not-a-uuid');
    }

    public function testInvalidAbonnementIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AbonnementId::fromString('not-a-uuid');
    }

    public function testInvalidStationnementIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StationnementId::fromString('not-a-uuid');
    }
}
