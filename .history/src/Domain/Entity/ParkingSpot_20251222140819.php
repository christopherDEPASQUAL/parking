<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ParkingSpotId;

final class ParkingSpot
{
    private ParkingSpotId $id;

    public function __construct(ParkingSpotId $id)
    {
        $this->id = $id;
    }

    public function getId(): ParkingSpotId
    {
        return $this->id;
    }
}
