<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\Entity\Vehicle;
use App\Domain\Exception\SpotAlreadyOccupiedException;

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