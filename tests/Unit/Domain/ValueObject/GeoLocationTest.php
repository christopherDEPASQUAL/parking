<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\GeoLocation;
use PHPUnit\Framework\TestCase;

final class GeoLocationTest extends TestCase
{
    public function testLongitudeNormalization(): void
    {
        $location = new GeoLocation(10.0, 190.0);

        self::assertSame(-170.0, $location->getLongitude());
    }

    public function testDistanceAndRadius(): void
    {
        $origin = new GeoLocation(48.8566, 2.3522);
        $same = new GeoLocation(48.8566, 2.3522);

        self::assertSame(0.0, $origin->distanceTo($same));
        self::assertTrue($origin->isWithinRadius($same, 0.0));
    }

    public function testNegativeRadiusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $location = new GeoLocation(48.8566, 2.3522);
        $location->isWithinRadius(new GeoLocation(48.8566, 2.3522), -1);
    }

    public function testInvalidLatitudeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new GeoLocation(100.0, 0.0);
    }

    public function testEqualsAndToString(): void
    {
        $first = new GeoLocation(48.8566, 2.3522);
        $second = new GeoLocation(48.8566, 2.3522);

        self::assertTrue($first->equals($second));
        self::assertSame('(48.856600, 2.352200)', (string) $first);
    }
}
