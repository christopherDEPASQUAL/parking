<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ParkingSpotId;

final class ParkingSpot
{
    public function __construct(private ParkingSpotId $id)
    {
    }

    public function getId(): ParkingSpotId
    {
        return $this->id;
    }
}
