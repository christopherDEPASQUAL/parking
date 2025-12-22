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

            throw new \InvalidArgumentException('Total capacity must be greater than zero.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->totalCapacity = $totalCapacity;
        $this->pricingPlan = $pricingPlan;
        $this->location = $location;
        $this->openingSchedule = $openingSchedule;
        $this->UserId = $UserId;
        $this->description = $description;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }