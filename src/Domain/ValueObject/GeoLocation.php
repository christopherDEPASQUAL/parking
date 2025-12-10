<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class GeoLocation
{
    private float $latitude;
    private float $longitude;

    public function __construct(float $latitude, float $longitude)
    {
        $this->latitude = $this->normalizeLatitude($latitude);
        $this->longitude = $this->normalizeLongitude($longitude);
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function __toString(): string
    {
        return sprintf('(%0.6f, %0.6f)', $this->latitude, $this->longitude);
    }

    public function equals(GeoLocation $other): bool
    {
        return $this->latitude === $other->latitude && $this->longitude === $other->longitude;
    }

    // Calcule la distance en kilomètres entre 2 localisations avec la formule de Harvesine
    public function distanceTo(GeoLocation $otpasher): float
    {
        $earthRadiusKm = 6371.0; // Rayon moyen de la terre en Kilomètres
        $lat1 = deg2rad($this->latitude);       
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($other->latitude);
        $lon2 = deg2rad($other->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    // Vérifie si une autre localisation est dans un rayon donné (en kilomètres).
    public function isWithinRadius(GeoLocation $other, float $radiusKm): bool
    {
        if ($radiusKm < 0) {
            throw new InvalidArgumentException('Le rayon doit etre positif.');
        }

        return $this->distanceTo($other) <= $radiusKm;
    }

    // Normalisation de la latitude entre -90 et 90 degrés avec arrondi.
    private function normalizeLatitude(float $latitude): float
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException('La latitude doit etre entre -90 et 90 degrés.');
        }

        return round($latitude, 6);
    }
    
    
    private function normalizeLongitude(float $longitude): float
    {
        // Wrap longitude to [-180, 180], then round for strict storage.
        $normalized = fmod($longitude + 180.0, 360.0);
        if ($normalized < 0) {
            $normalized += 360.0;
        }
        $normalized -= 180.0;

        return round($normalized, 6);
    }
}
