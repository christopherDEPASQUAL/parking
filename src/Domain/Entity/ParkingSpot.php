<?php
declare(strict_types=1);

namespace Domain\Entity;

use Domain\ValueObject\ParkingSpotId;
use Domain\Entity\Vehicle;
use Domain\Exception\SpotAlreadyOccupiedException;

final class ParkingSpot
{
    private ParkingSpotId $id;
    private bool $isOccupied = false;
    private ?Vehicle $vehicle = null;

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

    public function occupy(Vehicle $vehicle): void
    {
        if ($this->isOccupied) {
            throw new SpotAlreadyOccupiedException('La place est déjà occupée.');
        }

        $this->vehicle = $vehicle;
        $this->isOccupied = true;
    }

    public function free(): void
    {
        $this->vehicle = null;
        $this->isOccupied = false;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }
}
