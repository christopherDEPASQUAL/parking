<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use App\Domain\Exception\ParkingFullException;
use App\Domain\Exception\SpotAlreadyExistsException;
use App\Domain\Exception\ParkingFullException;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PricingPlan;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\StationnementId;

final class Parking
{
    private ParkingId $id;
    private string $name;
    private string $address;
    private ?string $description;
    private int $totalCapacity;
    private PricingPlan $pricingPlan;
    private GeoLocation $location;
    private OpeningSchedule $openingSchedule;
    private UserId $UserId; // (Owner)
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
        ?string $description = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        if ($totalCapacity <= 0) {
            throw new \InvalidArgumentException('La capacite totale doit etre superieure a zero.');
        }

        $this->id = $id;
        $this->name = trim($name);
        $this->address = trim($address);
        $this->description = $description !== null ? trim($description) : null;
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
    public function getDescription(): ?string { return $this->description; }
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

    /* ===================== SPOTS (pour ParkingTest) ===================== */

    public function isFull(): bool
    {
        return count($this->parkingSpots) >= $this->totalCapacity;
    }

    public function addSpot(ParkingSpot $spot): void
    {
        $id = $spot->getId()->getValue();

        if (isset($this->parkingSpots[$id])) {
            throw new SpotAlreadyExistsException();
        }

        if ($this->isFull()) {
            throw new ParkingFullException();
        }

        $this->parkingSpots[$id] = $spot;
        $this->touch();
    }

    public function getPhysicalFreeSpotsCount(): int
    {
        return $this->totalCapacity - count($this->parkingSpots);
    }

    /* ===================== ATTACH IDS ===================== */

    public function attachReservation(ReservationId $reservationId): void
    {
        $this->reservationIds[$reservationId->getValue()] = $reservationId;
    }

    public function attachAbonnement(AbonnementId $abonnementId): void
    {
        $this->abonnementIds[$abonnementId->getValue()] = $abonnementId;
    }

    public function attachStationnement(StationnementId $stationnementId): void
    {
        $this->stationnementIds[$stationnementId->getValue()] = $stationnementId;
    }

    public function computeAvailability(int $activeReservations, int $activeAbonnements, int $activeStationnements): int
    {
        $used = $activeReservations + $activeAbonnements + $activeStationnements;
        return max(0, $this->totalCapacity - $used);
    }

    public function freeSpotsAt(
        DateTimeImmutable $at,
        iterable $reservations,
        iterable $abonnements,
        iterable $stationnements
    ): int {
        $used = 0;

        foreach ($reservations as $reservation) {
            if (\method_exists($reservation, 'isActiveAt') && $reservation->isActiveAt($at)) {
                $used++;
            }
        }

        foreach ($abonnements as $abonnement) {
            if (\method_exists($abonnement, 'covers') && $abonnement->covers($at)) {
                $used++;
            }
        }

        foreach ($stationnements as $stationnement) {
            if (\method_exists($stationnement, 'isActiveAt') && $stationnement->isActiveAt($at)) {
                $used++;
            }
        }

        return max(0, $this->totalCapacity - $used);
    }

    public function isOpenAt(DateTimeImmutable $at): bool
    {
        return $this->openingSchedule->isOpenAt($at);
    }

    public function computePriceForDurationMinutes(int $minutes): int
    {
        return $this->pricingPlan->computePriceCents($minutes);
    }

    public function updateCapacity(int $newCapacity): void
    {
        if ($newCapacity <= 0) {
            throw new \InvalidArgumentException('La capacite totale doit etre superieure a zero.');
        }

        if ($newCapacity === $this->totalCapacity) {
            return;
        }

        $this->totalCapacity = $newCapacity;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
