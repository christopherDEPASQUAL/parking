<?php declare(strict_types=1);

namespace App\Application\DTO\Abonnements;

final class SubscribeToAbonnementResponse
{
    public function __construct(
        public readonly string $abonnementId,
        public readonly string $status
    ) {}
}

