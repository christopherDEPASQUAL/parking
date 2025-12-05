<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\Exception\SpotAlreadyOccupiedException;

final class ParkingSpot
{
    private ParkingSpotId $id;
    private bool $isOccupied = false;

    public function __construct(ParkingSpotId $id)
    {
        $this->id = $id;
    }

    public function getId(): ParkingSpotId
    {
        return $this->id;
    }

    public function isOccupied(): bool
    {
        return $this->isOccupied;
    }

    public function occupy(): void
    {
        if ($this->isOccupied) {
            throw new SpotAlreadyOccupiedException('Cette place est déjà occupée.');
        }

        $this->isOccupied = true;
    }

    public function free(): void
    {
        $this->isOccupied = false;
    }
}
