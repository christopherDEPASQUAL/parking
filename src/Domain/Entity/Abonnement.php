<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\InvalidAbonnementException;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
use App\Domain\ValueObject\UserId;

/**
 * Domain Entity: Abonnement (Subscription)
 *
 * Purpose:
 *  - Represents a subscription contract associated to a user/parking and an offer.
 *  - Validates eligibility/periods and active/inactive state.
 */
final class Abonnement
{
    private const MIN_DURATION_MONTHS = 1;
    private const MAX_DURATION_MONTHS = 12;

    private AbonnementId $id;
    private UserId $userId;
    private ParkingId $parkingId;
    private SubscriptionOfferId $offerId;
    /**
     * @var array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    private array $weeklyTimeSlots;
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private string $status;

    /**
     * @param array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}> $weeklyTimeSlots
     */
    public function __construct(
        AbonnementId $id,
        UserId $userId,
        ParkingId $parkingId,
        SubscriptionOfferId $offerId,
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
        $this->offerId = $offerId;
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

    public function offerId(): SubscriptionOfferId
    {
        return $this->offerId;
    }

    /**
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
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

    public function covers(\DateTimeImmutable $datetime): bool
    {
        return $this->coversTimeSlot($datetime);
    }

    public function coversTimeSlot(\DateTimeImmutable $datetime): bool
    {
        if (!$this->isActiveAt($datetime)) {
            return false;
        }

        $minutesOfWeek = $this->toWeekMinutes((int) $datetime->format('w'), $datetime->format('H:i'));

        foreach ($this->weeklyTimeSlots as $slot) {
            $start = $this->toWeekMinutes((int) $slot['start_day'], (string) $slot['start_time']);
            $end = $this->toWeekMinutes((int) $slot['end_day'], (string) $slot['end_time']);

            if ($start <= $end) {
                if ($minutesOfWeek >= $start && $minutesOfWeek <= $end) {
                    return true;
                }
            } else {
                if ($minutesOfWeek >= $start || $minutesOfWeek <= $end) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renew(\DateTimeImmutable $newEndDate): void
    {
        if ($newEndDate <= $this->endDate) {
            throw new InvalidAbonnementException('La nouvelle date de fin doit etre posterieure a la date de fin actuelle.');
        }

        $interval = $this->startDate->diff($newEndDate);
        $totalMonths = ($interval->y * 12) + $interval->m;

        if ($totalMonths > self::MAX_DURATION_MONTHS) {
            throw new InvalidAbonnementException('La duree maximale d\'un abonnement est de 12 mois.');
        }

        $this->endDate = $newEndDate;
    }

    public function suspend(): void
    {
        if ($this->status === 'suspended') {
            throw new InvalidAbonnementException('L\'abonnement est deja suspendu.');
        }

        $this->status = 'suspended';
    }

    public function reactivate(): void
    {
        if ($this->status !== 'suspended') {
            throw new InvalidAbonnementException('Seul un abonnement suspendu peut etre reactive.');
        }

        $this->status = 'active';
    }

    public function expire(): void
    {
        $this->status = 'expired';
    }

    private function toWeekMinutes(int $dayOfWeek, string $time): int
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new InvalidAbonnementException('Le jour doit etre compris entre 0 (dimanche) et 6 (samedi).');
        }

        $minutes = $this->timeToMinutes($time);
        return ($dayOfWeek * 1440) + $minutes;
    }

    private function timeToMinutes(string $time): int
    {
        if (!preg_match('/^(2[0-4]|[01]?\\d):([0-5]\\d)$/', $time, $m)) {
            throw new InvalidAbonnementException('L\'heure doit etre au format HH:MM.');
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];
        if ($hours === 24 && $minutes !== 0) {
            throw new InvalidAbonnementException('24:00 est la seule valeur valide avec 24 heures.');
        }

        return ($hours * 60) + $minutes;
    }

    private function validateDuration(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        if ($endDate <= $startDate) {
            throw new InvalidAbonnementException('La date de fin doit etre posterieure a la date de debut.');
        }

        $interval = $startDate->diff($endDate);
        $totalMonths = ($interval->y * 12) + $interval->m;

        if ($totalMonths < self::MIN_DURATION_MONTHS) {
            throw new InvalidAbonnementException('La duree minimale d\'un abonnement est de 1 mois.');
        }

        if ($totalMonths > self::MAX_DURATION_MONTHS) {
            throw new InvalidAbonnementException('La duree maximale d\'un abonnement est de 12 mois.');
        }
    }

    /**
     * @param array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}> $weeklyTimeSlots
     */
    private function validateTimeSlots(array $weeklyTimeSlots): void
    {
        if ($weeklyTimeSlots === []) {
            throw new InvalidAbonnementException('Au moins un creneau horaire doit etre defini.');
        }

        foreach ($weeklyTimeSlots as $slot) {
            if (!isset($slot['start_day'], $slot['end_day'], $slot['start_time'], $slot['end_time'])) {
                throw new InvalidAbonnementException('Chaque creneau doit contenir start_day, end_day, start_time et end_time.');
            }

            $startDay = (int) $slot['start_day'];
            $endDay = (int) $slot['end_day'];
            if ($startDay < 0 || $startDay > 6 || $endDay < 0 || $endDay > 6) {
                throw new InvalidAbonnementException('Les jours doivent etre compris entre 0 (dimanche) et 6 (samedi).');
            }

            $start = $this->toWeekMinutes($startDay, (string) $slot['start_time']);
            $end = $this->toWeekMinutes($endDay, (string) $slot['end_time']);
            if ($start === $end) {
                throw new InvalidAbonnementException('Le creneau ne peut pas avoir une duree nulle.');
            }
        }
    }

}
