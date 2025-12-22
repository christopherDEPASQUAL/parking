<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\InvalidAbonnementException;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;

/**
 * Offre d'abonnement configuree par un proprietaire de parking.
 */
final class SubscriptionOffer
{
    private SubscriptionOfferId $id;
    private ParkingId $parkingId;
    private string $label;
    private string $type;
    private int $priceCents;
    private string $status;
    /**
     * @var array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    private array $weeklyTimeSlots;

    /**
     * @param array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}> $weeklyTimeSlots
     */
    public function __construct(
        SubscriptionOfferId $id,
        ParkingId $parkingId,
        string $label,
        string $type,
        int $priceCents,
        array $weeklyTimeSlots,
        string $status = 'active'
    ) {
        $this->validateLabel($label);
        $this->validateType($type);
        $this->validatePrice($priceCents);
        $this->validateTimeSlots($weeklyTimeSlots);
        $this->validateStatus($status);

        $this->id = $id;
        $this->parkingId = $parkingId;
        $this->label = trim($label);
        $this->type = strtolower($type);
        $this->priceCents = $priceCents;
        $this->weeklyTimeSlots = $weeklyTimeSlots;
        $this->status = $status;
    }

    public function id(): SubscriptionOfferId
    {
        return $this->id;
    }

    public function parkingId(): ParkingId
    {
        return $this->parkingId;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function priceCents(): int
    {
        return $this->priceCents;
    }

    /**
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    public function weeklyTimeSlots(): array
    {
        return $this->weeklyTimeSlots;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
    }

    public function activate(): void
    {
        $this->status = 'active';
    }

    private function validateLabel(string $label): void
    {
        if (trim($label) === '') {
            throw new InvalidAbonnementException('Le libelle de l\'offre est obligatoire.');
        }
    }

    private function validateType(string $type): void
    {
        $allowed = ['full', 'weekend', 'evening', 'custom'];
        if (!in_array(strtolower($type), $allowed, true)) {
            throw new InvalidAbonnementException('Type d\'abonnement invalide.');
        }
    }

    private function validateStatus(string $status): void
    {
        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidAbonnementException('Statut d\'offre invalide.');
        }
    }

    private function validatePrice(int $priceCents): void
    {
        if ($priceCents < 0) {
            throw new InvalidAbonnementException('Le prix doit etre positif ou nul.');
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

    private function toWeekMinutes(int $dayOfWeek, string $time): int
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new InvalidAbonnementException('Le jour doit etre compris entre 0 (dimanche) et 6 (samedi).');
        }

        return ($dayOfWeek * 1440) + $this->timeToMinutes($time);
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
}
