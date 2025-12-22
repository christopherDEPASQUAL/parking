<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use App\Domain\Exception\ParkingFullException;
use App\Domain\Exception\SpotAlreadyExistsException;
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
    private int $totalCapacity;
    private PricingPlan $pricingPlan;
    private GeoLocation $location;
    private OpeningSchedule $openingSchedule;
    private UserId $userId;
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
        UserId $userId
    ) {
        if ($totalCapacity <= 0) {
            throw new \InvalidArgumentException('Capacity must be greater than zero');
        }

        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->totalCapacity = $totalCapacity;
        $this->pricingPlan = $pricingPlan;
        $this->location = $location;
        $this->openingSchedule = $openingSchedule;
        $this->userId = $userId;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    /* ================= GETTERS ================= */

    public function getId(): ParkingId { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getAddress(): string { return $this->address; }
    public function getTotalCapacity(): int { return $this->totalCapacity; }
    public function getPricingPlan(): PricingPlan { return $this->pricingPlan; }
    public function getLocation(): GeoLocation { return $this->location; }
    public function getOpeningSchedule(): OpeningSchedule { return $this->openingSchedule; }
    public function getUserId(): UserId { return $this->userId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    /* ================= SPOTS ================= */

    public function isFull(): bool
    {
        return count($this->parkingSpots) >= $this->totalCapacity;
    }

    public function addSpot(ParkingSpot $spot): void
    {
        $spotId = $spot->getId()->getValue();

        if (isset($this->parkingSpots[$spotId])) {
            throw new SpotAlreadyExistsException();
        }

        if ($this->isFull()) {
            throw new ParkingFullException();
        }

        $this->parkingSpots[$spotId] = $spot;
        $this->touch();
    }

    public function getPhysicalFreeSpotsCount(): int
    {
        return $this->totalCapacity - count($this->parkingSpots);
    }

    /* ================= BUSINESS ================= */

    public function freeSpotsAt(
        DateTimeImmutable $at,
        iterable $reservations,
        iterable $abonnements,
        iterable $stationnements
    ): int {
        $used = 0;

        foreach ($reservations as $r) {
            if (method_exists($r, 'isActiveAt') && $r->isActiveAt($at)) {
                $used++;
            }
        }

        foreach ($abonnements as $a) {
            if (method_exists($a, 'covers') && $a->covers($at)) {
                $used++;
            }
        }

        foreach ($stationnements as $s) {
            if (method_exists($s, 'isActiveAt') && $s->isActiveAt($at)) {
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

    public function attachReservation(ReservationId $id): void
    {
        $this->reservationIds[$id->getValue()] = $id;
    }

    public function attachAbonnement(AbonnementId $id): void
    {
        $this->abonnementIds[$id->getValue()] = $id;
    }

    public function attachStationnement(StationnementId $id): void
    {
        $this->stationnementIds[$id->getValue()] = $id;
    }

    public function computeAvailability(int $r, int $a, int $s): int
    {
        return max(0, $this->totalCapacity - ($r + $a + $s));
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
