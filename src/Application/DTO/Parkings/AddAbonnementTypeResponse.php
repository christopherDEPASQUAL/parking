<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class AddAbonnementTypeResponse
{
    public function __construct(
        public readonly string $abonnementTypeId
    ) {}
}

