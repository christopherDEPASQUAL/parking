<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ListOutOfSlotDriversResponse
{
    public function __construct(
        public readonly array $drivers
    ) {}
}

