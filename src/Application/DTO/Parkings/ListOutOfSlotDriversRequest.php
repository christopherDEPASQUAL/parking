<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ListOutOfSlotDriversRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly string $ownerId
    ) {}
}

