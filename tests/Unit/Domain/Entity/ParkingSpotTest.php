<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\ParkingSpot;
use App\Domain\ValueObject\ParkingSpotId;
use PHPUnit\Framework\TestCase;

final class ParkingSpotTest extends TestCase
{
    public function testCreateSpot(): void
    {
        $id = ParkingSpotId::fromString('11111111-1111-4111-8111-111111111111');
        $spot = new ParkingSpot($id);

        self::assertSame($id, $spot->getId());
    }
}
