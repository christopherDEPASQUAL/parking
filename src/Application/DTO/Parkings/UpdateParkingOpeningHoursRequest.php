<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class UpdateParkingOpeningHoursRequest
{
    /**
     * @param array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}> $openingHours
     */
    public function __construct(
        public readonly string $parkingId,
        public readonly array $openingHours
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
            $data['opening_hours'] ?? throw new \InvalidArgumentException('opening_hours is required')
        );
    }
}
