<?php

declare(strict_types=1);

namespace Domain\Entity;

use Domain\ValueObject\ParkingId;
use Domain\Entity\PricingPlan;
use Domain\Entity\ParkingSpot;
use Domain\Exception\ParkingFullException;
use DateTimeImmutable;
use InvalidArgumentException;
private GeoLocation $location;

/**
 * Entité Parking : Aggregate Root (Racine d'Agrégat)
 * Représente un parking et gère ses règles métier.
 */
final class Parking
{
    private ParkingId $id;
    private string $name;
    private string $address;
    private int $totalCapacity;
    private PricingPlan $pricingPlan;

    /** @var array<string, ParkingSpot> */
    private array $parkingSpots = [];

    private DateTimeImmutable $createdAt;

    public function __construct(
        ParkingId $id,
        string $name,
        string $address,
        int $totalCapacity,
        PricingPlan $pricingPlan,
        GeoLocation $location,
        ?DateTimeImmutable $createdAt = null
    ) {
        if ($totalCapacity <= 0) {
            throw new InvalidArgumentException('La capacité totale doit être supérieure à zéro.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->totalCapacity = $totalCapacity;
        $this->pricingPlan = $pricingPlan;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getLocation(): GeoLocation
    {
        return $this->location;
    }

    public function getId(): ParkingId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getTotalCapacity(): int
    {
        return $this->totalCapacity;
    }

    public function getPricingPlan(): PricingPlan
    {
        return $this->pricingPlan;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Change le plan tarifaire du parking.
     */
    public function changePricingPlan(PricingPlan $newPlan): void
    {
        $this->pricingPlan = $newPlan;
    }

    /**
     * Ajoute une place de parking en respectant la capacité maximale.
     *
     * @throws ParkingFullException
     */
    public function addSpot(ParkingSpot $spot): void
    {
        if ($this->isFull()) {
            throw new ParkingFullException('Impossible d\'ajouter une place, le parking a atteint sa capacité maximale.');
        }

        $this->parkingSpots[$spot->getId()->getValue()] = $spot;
    }

    public function isFull(): bool
    {
        return count($this->parkingSpots) >= $this->totalCapacity;
    }

    /**
     * @return array<string, ParkingSpot>
     */
    public function getParkingSpots(): array
    {
        return $this->parkingSpots;
    }
}
