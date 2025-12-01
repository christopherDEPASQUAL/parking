<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use Domain\Exception\ParkingFullException;
use Domain\Exception\SpotAlreadyExistsException;
use Domain\ValueObject\GeoLocation;
use Domain\ValueObject\OpeningSchedule; 
use Domain\ValueObject\ParkingId;
use Domain\ValueObject\PricingPlan;        
use Domain\ValueObject\UserId;            
use Domain\ValueObject\ReservationId;
use Domain\ValueObject\AbonnementId;
use Domain\ValueObject\StationnementId;

/**
 * Aggregate root Parking : disponibilité, spots, règles d’accès.
 */
final class Parking
{
    private ParkingId $id;
    private string $name;
    private string $address;
    private int $totalCapacity;
    private PricingPlan $pricingPlan;
    private GeoLocation $location;
    private OpeningSchedule $openingSchedule;
    private UserId $UserId; //(Owner)
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    /** @var array<string, ParkingSpot> */
    private array $parkingSpots = [];

    /** @var array<string, ReservationId> */
    private array $reservationIds = [];

    /** @var array<string, AbonnementId> */
    private array $abonnementIds = [];

    /** @var array<string, StationnementId> */
    private array $stationnementIds = [];

    public function __construct(
        ParkingId $id,
        string $name,
        string $address,
        int $totalCapacity,
        PricingPlan $pricingPlan,
        GeoLocation $location,
        OpeningSchedule $openingSchedule,
        UserId $UserId,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        if ($totalCapacity <= 0) {
            throw new \InvalidArgumentException('La capacite totale doit etre superieure a zero.');
        }

        $this->id = $id;
        $this->name = trim($name);
        $this->address = trim($address);
        $this->totalCapacity = $totalCapacity;
        $this->pricingPlan = $pricingPlan;
        $this->location = $location;
        $this->openingSchedule = $openingSchedule;
        $this->UserId = $UserId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? $this->createdAt;
    }

    public function getId(): ParkingId { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getAddress(): string { return $this->address; }
    public function getTotalCapacity(): int { return $this->totalCapacity; }
    public function getPricingPlan(): PricingPlan { return $this->pricingPlan; }
    public function getLocation(): GeoLocation { return $this->location; }
    public function getOpeningSchedule(): OpeningSchedule { return $this->openingSchedule; }
    public function getUserId(): UserId { return $this->UserId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    /** Change le plan tarifaire. */
    public function changePricingPlan(PricingPlan $newPlan): void
    {
        $this->pricingPlan = $newPlan;
        $this->touch();
    }

    /** Change les horaires d’ouverture. */
    public function changeOpeningSchedule(OpeningSchedule $schedule): void
    {
        $this->openingSchedule = $schedule;
        $this->touch();
    }

    /** Ajoute une place si capacité non dépassée et id unique. */
    public function addSpot(ParkingSpot $spot): void
    {
        $spotId = $spot->getId()->getValue();
        if (isset($this->parkingSpots[$spotId])) {
            throw new SpotAlreadyExistsException('Cette place existe deja dans ce parking.');
        }
        if ($this->isFull()) {
            throw new ParkingFullException('Capacite maximale atteinte.');
        }
        $this->parkingSpots[$spotId] = $spot;
        $this->touch();
    }

    /** Marque une réservation liée à ce parking (pour suivi de dispo). */
    public function attachReservation(ReservationId $reservationId): void
    {
        $this->reservationIds[$reservationId->getValue()] = $reservationId;
    }

    /** Marque un abonnement lié à ce parking. */
    public function attachAbonnement(AbonnementId $abonnementId): void
    {
        $this->abonnementIds[$abonnementId->getValue()] = $abonnementId;
    }

    /** Marque un stationnement actif lié à ce parking. */
    public function attachStationnement(StationnementId $stationnementId): void
    {
        $this->stationnementIds[$stationnementId->getValue()] = $stationnementId;
    }

    public function isFull(): bool
    {
        // capacité statique par spots ajoutés
        return \count($this->parkingSpots) >= $this->totalCapacity;
    }

    /** Places libres à un instant t selon la capacité physique et slots. */
    public function getPhysicalFreeSpotsCount(): int
    {
        return $this->totalCapacity - \count($this->parkingSpots);
    }

    /**
     * Nombre de places restantes tenant compte des réservations/abonnements/stationnements actifs.
     * Les services de domaine (non montrés ici) doivent fournir les counts à t.
     */
    public function computeAvailability(int $activeReservations, int $activeAbonnements, int $activeStationnements): int
    {
        $used = $activeReservations + $activeAbonnements + $activeStationnements;
        return max(0, $this->totalCapacity - $used);
    }

    /** Vérifie si le parking est ouvert à un instant donné. */
    public function isOpenAt(DateTimeImmutable $at): bool
    {
        return $this->openingSchedule->isOpenAt($at);
    }

    /** Calcul du prix pour une durée (en minutes) en utilisant le plan tarifaire. */
    public function computePriceForDurationMinutes(int $minutes): int
    {
        return $this->pricingPlan->computePriceCents($minutes);
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
