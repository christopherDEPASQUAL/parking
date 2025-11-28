<?php
declare(strict_types=1);

namespace Domain\ValueObject;

use InvalidArgumentException;

final class GeoLocation
{
    private float $latitude;
    private float $longitude;

    public function __construct(float $latitude, float $longitude)
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees.');
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees.');
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
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
        return sprintf('(%f, %f)', $this->latitude, $this->longitude);
    }

    public function equals(GeoLocation $other): bool
    {
        return $this->latitude === $other->latitude && $this->longitude === $other->longitude;
    }
}
