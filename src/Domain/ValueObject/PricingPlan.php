<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value Object immuable representant une grille tarifaire par tranche.
 * - La tarification est calculee par palier (tier) de maniere cumulative.
 * - Un prix par defaut est applique au temps qui depasse le dernier palier defini.
 * - Le pas de facturation est fixe a 15 minutes.
 */
final class PricingPlan
{
    private const DEFAULT_STEP_MINUTES = 15;
    private const PENALTY_CENTS = 2000;

    /**
     * @var array<int, array{upToMinutes:int, pricePerStepCents:int}>
     */
    private array $tiers;

    private int $stepMinutes;
    private int $defaultPricePerStepCents;
    private int $overstayPenaltyCents;
    /**
     * @var array<string, int>
     */
    private array $subscriptionPrices;

    public function __construct(
        array $tiers,
        int $defaultPricePerStepCents,
        int $overstayPenaltyCents = self::PENALTY_CENTS,
        array $subscriptionPrices = [],
        int $stepMinutes = self::DEFAULT_STEP_MINUTES
    ) {
        $this->stepMinutes = $this->validateStep($stepMinutes);
        $this->tiers = $this->validateTiers($tiers);
        $this->defaultPricePerStepCents = $this->validatePrice($defaultPricePerStepCents, 'Default price per step');
        $this->overstayPenaltyCents = $this->validatePrice($overstayPenaltyCents, 'Overstay penalty');
        $this->subscriptionPrices = $this->validateSubscriptionPrices($subscriptionPrices);
    }

    /**
     * Calcule le prix TOTAL d'un stationnement pour une duree donnee.
     *
     * @param int $minutes Duree totale du stationnement en minutes.
     */
    public function computePriceCents(int $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $totalPriceCents = 0.0;
        $chargedSteps = (int) ceil($minutes / $this->stepMinutes);
        $minutesToCharge = $chargedSteps * $this->stepMinutes;
        $remainingMinutesToCharge = $minutesToCharge;

        $lastUpToMinutes = 0;

        foreach ($this->tiers as $tier) {
            $upTo = $tier['upToMinutes'];
            $pricePerStep = $tier['pricePerStepCents'];

            $minutesInTier = $upTo - $lastUpToMinutes;
            $minutesToChargeInTier = min($remainingMinutesToCharge, $minutesInTier);

            if ($minutesToChargeInTier > 0) {
                $stepsInTier = $minutesToChargeInTier / $this->stepMinutes;

                $totalPriceCents += $stepsInTier * $pricePerStep;
                $remainingMinutesToCharge -= $minutesToChargeInTier;
            }

            $lastUpToMinutes = $upTo;

            if ($remainingMinutesToCharge <= 0) {
                return (int) round($totalPriceCents);
            }
        }

        if ($remainingMinutesToCharge > 0) {
            $stepsRemaining = $remainingMinutesToCharge / $this->stepMinutes;
            $totalPriceCents += $stepsRemaining * $this->defaultPricePerStepCents;
        }

        return (int) round($totalPriceCents);
    }

    /**
     * Calcule le montant total du, incluant la penalite si depassement.
     */
    public function computeOverstayPriceCents(int $reservedMinutes, int $actualMinutes): int
    {
        $base = $this->computePriceCents($actualMinutes);

        if ($actualMinutes > $reservedMinutes) {
            return $base + $this->overstayPenaltyCents;
        }

        return $base;
    }

    public function getStepMinutes(): int
    {
        return $this->stepMinutes;
    }

    public function getTiers(): array
    {
        return $this->tiers;
    }

    public function getDefaultPricePerStepCents(): int
    {
        return $this->defaultPricePerStepCents;
    }

    public function getOverstayPenaltyCents(): int
    {
        return $this->overstayPenaltyCents;
    }

    public function getSubscriptionPriceCents(string $type): int
    {
        $key = strtolower($type);
        return $this->subscriptionPrices[$key] ?? 0;
    }

    public function getSubscriptionPrices(): array
    {
        return $this->subscriptionPrices;
    }

    public function toArray(): array
    {
        $subscriptionPrices = $this->subscriptionPrices === []
            ? (object) []
            : $this->subscriptionPrices;

        return [
            'stepMinutes' => $this->stepMinutes,
            'tiers' => $this->tiers,
            'defaultPricePerStepCents' => $this->defaultPricePerStepCents,
            'overstayPenaltyCents' => $this->overstayPenaltyCents,
            'subscriptionPrices' => $subscriptionPrices,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['tiers'], $data['defaultPricePerStepCents'])) {
            throw new InvalidArgumentException('Missing required pricing data.');
        }

        return new self(
            $data['tiers'],
            (int) $data['defaultPricePerStepCents'],
            isset($data['overstayPenaltyCents']) ? (int) $data['overstayPenaltyCents'] : self::PENALTY_CENTS,
            $data['subscriptionPrices'] ?? [],
            isset($data['stepMinutes']) ? (int) $data['stepMinutes'] : self::DEFAULT_STEP_MINUTES
        );
    }

    private function validateStep(int $stepMinutes): int
    {
        if ($stepMinutes <= 0) {
            throw new InvalidArgumentException('Step minutes must be positive.');
        }
        if ($stepMinutes !== self::DEFAULT_STEP_MINUTES) {
            throw new InvalidArgumentException('Step minutes must be 15.');
        }

        return $stepMinutes;
    }

    /**
     * @param array<int, array{upToMinutes:int, pricePerStepCents:int}> $tiers
     * @return array<int, array{upToMinutes:int, pricePerStepCents:int}>
     */
    private function validateTiers(array $tiers): array
    {
        if ($tiers === []) {
            return $tiers;
        }

        usort($tiers, static fn ($a, $b) => $a['upToMinutes'] <=> $b['upToMinutes']);

        $lastUpTo = 0;
        foreach ($tiers as $tier) {
            if (!isset($tier['upToMinutes'], $tier['pricePerStepCents'])) {
                throw new InvalidArgumentException('Each tier must have upToMinutes and pricePerStepCents.');
            }

            $upTo = (int) $tier['upToMinutes'];
            $this->validatePrice((int) $tier['pricePerStepCents'], 'Tier price per step');

            if ($upTo <= 0 || $upTo % $this->stepMinutes !== 0) {
                throw new InvalidArgumentException('Tier upToMinutes must be a positive multiple of step minutes.');
            }

            if ($upTo <= $lastUpTo) {
                throw new InvalidArgumentException('Tiers must be strictly increasing and without overlaps.');
            }

            $lastUpTo = $upTo;
        }

        return $tiers;
    }

    private function validatePrice(int $price, string $label): int
    {
        if ($price < 0) {
            throw new InvalidArgumentException($label . ' must be positive or zero.');
        }

        return $price;
    }

    private function validateSubscriptionPrices(array $prices): array
    {
        $allowed = ['full', 'weekend', 'evening', 'custom'];
        $normalized = [];

        foreach ($prices as $type => $price) {
            $key = strtolower((string) $type);
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $normalized[$key] = $this->validatePrice((int) $price, 'Subscription price');
        }

        return $normalized;
    }
}
