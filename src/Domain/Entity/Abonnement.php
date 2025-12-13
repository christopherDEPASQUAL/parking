<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\Exception\InvalidAbonnementException;

/**
 * Domain Entity: Abonnement (Subscription)
 *
 * Purpose:
 *  - Represents a subscription product or contract associated to a user/parking.
 *  - Validates eligibility/periods and active/inactive state.
 */
final class Abonnement
{
    private const MIN_DURATION_MONTHS = 1;
    private const MAX_DURATION_MONTHS = 12;

    private AbonnementId $id;
    private UserId $userId;
    private ParkingId $parkingId;
    private array $weeklyTimeSlots;
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private string $status;

    public function __construct(
        AbonnementId $id,
        UserId $userId,
        ParkingId $parkingId,
        array $weeklyTimeSlots,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $status = 'active'
    ) {
        $this->validateDuration($startDate, $endDate);
        $this->validateTimeSlots($weeklyTimeSlots);

        $this->id = $id;
        $this->userId = $userId;
        $this->parkingId = $parkingId;
        $this->weeklyTimeSlots = $weeklyTimeSlots;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
    }

    public function id(): AbonnementId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function parkingId(): ParkingId
    {
        return $this->parkingId;
    }

    public function weeklyTimeSlots(): array
    {
        return $this->weeklyTimeSlots;
    }

    public function startDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isActiveAt(\DateTimeImmutable $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function coversTimeSlot(\DateTimeImmutable $datetime): bool
    {
        if (!$this->isActiveAt($datetime)) {
            return false;
        }

        $dayOfWeek = (int) $datetime->format('N');
        $time = $datetime->format('H:i');

        foreach ($this->weeklyTimeSlots as $slot) {
            if ($slot['day'] === $dayOfWeek && $time >= $slot['start'] && $time <= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    public function renew(\DateTimeImmutable $newEndDate): void
    {
        if ($newEndDate <= $this->endDate) {
            throw new InvalidAbonnementException('La nouvelle date de fin doit être postérieure à la date de fin actuelle.');
        }

        $interval = $this->startDate->diff($newEndDate);
        $totalMonths = ($interval->y * 12) + $interval->m;

        if ($totalMonths > self::MAX_DURATION_MONTHS) {
            throw new InvalidAbonnementException('La durée maximale d\'un abonnement est de 12 mois.');
        }

        $this->endDate = $newEndDate;
    }

    public function suspend(): void
    {
        if ($this->status === 'suspended') {
            throw new InvalidAbonnementException('L\'abonnement est déjà suspendu.');
        }

        $this->status = 'suspended';
    }

    public function reactivate(): void
    {
        if ($this->status !== 'suspended') {
            throw new InvalidAbonnementException('Seul un abonnement suspendu peut être réactivé.');
        }

        $this->status = 'active';
    }

    public function expire(): void
    {
        $this->status = 'expired';
    }

    private function validateDuration(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        if ($endDate <= $startDate) {
            throw new InvalidAbonnementException('La date de fin doit être postérieure à la date de début.');
        }

        $interval = $startDate->diff($endDate);
        $totalMonths = ($interval->y * 12) + $interval->m;

        if ($totalMonths < self::MIN_DURATION_MONTHS) {
            throw new InvalidAbonnementException('La durée minimale d\'un abonnement est de 1 mois.');
        }

        if ($totalMonths > self::MAX_DURATION_MONTHS) {
            throw new InvalidAbonnementException('La durée maximale d\'un abonnement est de 12 mois.');
        }
    }

    private function validateTimeSlots(array $weeklyTimeSlots): void
    {
        if (empty($weeklyTimeSlots)) {
            throw new InvalidAbonnementException('Au moins un créneau horaire doit être défini.');
        }

        foreach ($weeklyTimeSlots as $slot) {
            if (!isset($slot['day'], $slot['start'], $slot['end'])) {
                throw new InvalidAbonnementException('Chaque créneau doit contenir day, start et end.');
            }

            if ($slot['day'] < 1 || $slot['day'] > 7) {
                throw new InvalidAbonnementException('Le jour doit être compris entre 1 (lundi) et 7 (dimanche).');
            }

            if ($slot['start'] >= $slot['end']) {
                throw new InvalidAbonnementException('L\'heure de début doit être antérieure à l\'heure de fin.');
            }
        }
    }
}
