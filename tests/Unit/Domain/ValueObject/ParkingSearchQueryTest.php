<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\ParkingSearchQuery;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class ParkingSearchQueryTest extends TestCase
{
    public function testValidQueryNormalizesName(): void
    {
        $query = new ParkingSearchQuery(
            new GeoLocation(48.0, 2.0),
            1.5,
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            2,
            1500,
            UserId::fromString('11111111-1111-4111-8111-111111111111'),
            ''
        );

        self::assertSame(1.5, $query->radiusKm());
        self::assertSame(2, $query->minimumFreeSpots());
        self::assertNull($query->name());
    }

    public function testInvalidRadiusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ParkingSearchQuery(
            new GeoLocation(48.0, 2.0),
            -1,
            new \DateTimeImmutable()
        );
    }

    public function testInvalidMinimumSpotsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ParkingSearchQuery(
            new GeoLocation(48.0, 2.0),
            1,
            new \DateTimeImmutable(),
            0
        );
    }

    public function testOptionalFieldsAreExposed(): void
    {
        $ownerId = UserId::fromString('11111111-1111-4111-8111-111111111111');
        $endsAt = new \DateTimeImmutable('2025-01-01 12:00:00');

        $query = new ParkingSearchQuery(
            new GeoLocation(48.0, 2.0),
            2.5,
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            1,
            2000,
            $ownerId,
            'Central',
            $endsAt
        );

        self::assertSame(2000, $query->maxPriceCents());
        self::assertSame($ownerId, $query->ownerId());
        self::assertSame('Central', $query->name());
        self::assertSame($endsAt, $query->endsAt());
    }

    public function testCenterAndAtAccessors(): void
    {
        $center = new GeoLocation(48.0, 2.0);
        $at = new \DateTimeImmutable('2025-01-01 09:00:00');
        $query = new ParkingSearchQuery($center, 1.0, $at);

        self::assertSame($center, $query->center());
        self::assertSame($at, $query->at());
    }
}
