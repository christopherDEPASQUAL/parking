<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value Object immuable representant une grille tarifaire par tranche.
 * * - La tarification est calculée par palier (tier) de manière cumulative.
 * - Un prix par défaut est appliqué au temps qui dépasse le dernier palier défini.
 * - Le pas de facturation est fixé à 15 minutes.
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

    public function __construct(
        array $tiers,
        int $defaultPricePerStepCents,
        int $overstayPenaltyCents = self::PENALTY_CENTS,
        int $stepMinutes = self::DEFAULT_STEP_MINUTES
    ) {
        $this->stepMinutes = $this->validateStep($stepMinutes);
        // Valide les prix des tiers avant de valider la structure complète
        $this->tiers = $this->validateTiers($tiers);
        $this->defaultPricePerStepCents = $this->validatePrice($defaultPricePerStepCents, 'Prix par défaut par tranche');
        $this->overstayPenaltyCents = $this->validatePrice($overstayPenaltyCents, 'Pénalité de dépassement');
    }

    /**
     * Calcule le prix TOTAL d'un stationnement pour une durée donnée, 
     * en appliquant la tarification progressive/dégressive par palier.
     * * @param int $minutes Durée totale du stationnement en minutes.
     * @return int Le prix total en centimes d'euro (arrondi à l'entier).
     */
    public function computePriceCents(int $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $totalPriceCents = 0.0;
        
        // Arrondit au nombre de pas supérieur (ex: 17 min -> 2 pas de 15 min = 30 min)
        $chargedSteps = (int) ceil($minutes / $this->stepMinutes);
        $minutesToCharge = $chargedSteps * $this->stepMinutes;
        $remainingMinutesToCharge = $minutesToCharge;

        $lastUpToMinutes = 0;

        // 1. Facturation par paliers définis ($tiers)
        foreach ($this->tiers as $tier) {
            $upTo = $tier['upToMinutes'];
            $pricePerStep = $tier['pricePerStepCents'];

            // Minutes couvertes par CE palier (ex: si palier 60 min et dernier était 30 min, couvre 30 min)
            $minutesInTier = $upTo - $lastUpToMinutes;
            
            // Minutes facturables dans CE palier (au max ce que nous avons encore à facturer)
            $minutesToChargeInTier = min($remainingMinutesToCharge, $minutesInTier);
            
            if ($minutesToChargeInTier > 0) {
                // Nombre de pas (tranches de 15 min) dans cette section
                $stepsInTier = $minutesToChargeInTier / $this->stepMinutes;
                
                $totalPriceCents += $stepsInTier * $pricePerStep;
                $remainingMinutesToCharge -= $minutesToChargeInTier;
            }

            $lastUpToMinutes = $upTo;

            if ($remainingMinutesToCharge <= 0) {
                return (int) round($totalPriceCents);
            }
        }

        // 2. Facturation du temps restant (Dépassement de la grille tarifaire)
        if ($remainingMinutesToCharge > 0) {
            $stepsRemaining = $remainingMinutesToCharge / $this->stepMinutes;
            
            // Applique le PRIX PAR DÉFAUT pour le temps de dépassement de la grille
            $totalPriceCents += $stepsRemaining * $this->defaultPricePerStepCents;
        }

        return (int) round($totalPriceCents);
    }

    /**
     * Calcule le montant total dû, incluant la facturation du temps réel
     * et l'éventuelle pénalité fixe de 20 € si le temps réel dépasse le temps réservé.
     * * @param int $reservedMinutes Durée de la réservation/abonnement initiale.
     * @param int $actualMinutes Durée réelle du stationnement.
     * @return int Le prix total en centimes (incluant pénalité et temps additionnel facturé).
     */
    public function computeOverstayPriceCents(int $reservedMinutes, int $actualMinutes): int
    {
        // 1. Calcul du coût du temps réel (utilise la tarification progressive + prix par défaut si hors grille)
        $base = $this->computePriceCents($actualMinutes);

        // 2. Application de la pénalité fixe de 20 €
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

    public function toArray(): array
    {
        return [
            'stepMinutes' => $this->stepMinutes,
            'tiers' => $this->tiers,
            'defaultPricePerStepCents' => $this->defaultPricePerStepCents,
            'overstayPenaltyCents' => $this->overstayPenaltyCents,
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
            isset($data['stepMinutes']) ? (int) $data['stepMinutes'] : self::DEFAULT_STEP_MINUTES
        );
    }
    
    // --- Validation (Méthodes privées) ---

    private function validateStep(int $stepMinutes): int
    {
        if ($stepMinutes <= 0) {
            throw new InvalidArgumentException('La facturation par tranche doit etre positive.');
        }
        // Enforce 15 minutes as per project constraint
        if ($stepMinutes !== self::DEFAULT_STEP_MINUTES) {
            throw new InvalidArgumentException('La facturation par tranche doit etre de 15 minutes.');
        }

        return $stepMinutes;
    }

    /**
     * Valide la structure des paliers, assure l'ordre et l'absence de chevauchement.
     * @param array<int, array{upToMinutes:int, pricePerStepCents:int}> $tiers
     * @return array<int, array{upToMinutes:int, pricePerStepCents:int}>
     */
    private function validateTiers(array $tiers): array
    {
        if ($tiers === []) {
             // Il est plus logique de laisser le prix par défaut couvrir le cas "vide"
            return $tiers; 
        }

        // Tri pour garantir l'ordre et vérifier les chevauchements facilement
        usort($tiers, static fn ($a, $b) => $a['upToMinutes'] <=> $b['upToMinutes']);

        $lastUpTo = 0;
        foreach ($tiers as $tier) {
            if (!isset($tier['upToMinutes'], $tier['pricePerStepCents'])) {
                throw new InvalidArgumentException('Each tier must have upToMinutes and pricePerStepCents.');
            }

            $upTo = (int) $tier['upToMinutes'];
            $price = $this->validatePrice((int) $tier['pricePerStepCents'], 'Tier price per step');

            // Doit être un multiple du pas de facturation (15 min)
            if ($upTo <= 0 || $upTo % $this->stepMinutes !== 0) {
                throw new InvalidArgumentException('Tier upToMinutes must be a positive multiple of step minutes.');
            }

            // Doit être strictement supérieur au palier précédent
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
            // Permet 0 centimes (gratuit)
            throw new InvalidArgumentException($label . ' must be positive or zero.');
        }

        return $price;
    }
    

} 