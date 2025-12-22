<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\SubscriptionOffer;
use App\Domain\Exception\InvalidAbonnementException;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
use PHPUnit\Framework\TestCase;

final class SubscriptionOfferTest extends TestCase
{
    private function createSlots(): array
    {
        return [
            ['start_day' => 1, 'end_day' => 1, 'start_time' => '08:00', 'end_time' => '18:00'],
        ];
    }

    public function testCreateValidOffer(): void
    {
        $offer = new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            2000,
            $this->createSlots()
        );

        self::assertSame('weekend', $offer->type());
        self::assertSame('active', $offer->status());
    }

    public function testInvalidTypeThrows(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Invalid',
            'vip',
            1000,
            $this->createSlots()
        );
    }

    public function testInvalidStatusThrows(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            1000,
            $this->createSlots(),
            'paused'
        );
    }

    public function testInvalidPriceThrows(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            -1,
            $this->createSlots()
        );
    }

    public function testInvalidSlotsThrow(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            1000,
            []
        );
    }

    public function testActivateDeactivate(): void
    {
        $offer = new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            1000,
            $this->createSlots()
        );

        $offer->deactivate();
        self::assertSame('inactive', $offer->status());

        $offer->activate();
        self::assertSame('active', $offer->status());
    }

    public function testEmptyLabelThrows(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            ' ',
            'weekend',
            1000,
            $this->createSlots()
        );
    }

    public function testWeeklyTimeSlotsAreExposed(): void
    {
        $slots = $this->createSlots();
        $offer = new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            1000,
            $slots
        );

        self::assertSame($slots, $offer->weeklyTimeSlots());
    }

    public function testGetterValues(): void
    {
        $offerId = SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111');
        $parkingId = ParkingId::fromString('parking-1');
        $offer = new SubscriptionOffer(
            $offerId,
            $parkingId,
            'Soir',
            'evening',
            1500,
            $this->createSlots()
        );

        self::assertSame($offerId, $offer->id());
        self::assertSame($parkingId, $offer->parkingId());
        self::assertSame('Soir', $offer->label());
        self::assertSame('evening', $offer->type());
        self::assertSame(1500, $offer->priceCents());
    }

    public function testInvalidTimeFormatThrows(): void
    {
        $this->expectException(InvalidAbonnementException::class);

        new SubscriptionOffer(
            SubscriptionOfferId::fromString('11111111-1111-4111-8111-111111111111'),
            ParkingId::fromString('parking-1'),
            'Week-end',
            'weekend',
            1000,
            [
                ['start_day' => 1, 'end_day' => 1, 'start_time' => '24:30', 'end_time' => '25:00'],
            ]
        );
    }
}
