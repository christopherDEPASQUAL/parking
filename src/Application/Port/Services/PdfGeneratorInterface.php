<?php declare(strict_types=1);

namespace App\Application\Port\Services;

interface PdfGeneratorInterface
{
    public function generate(string $html, string $filename): ?string;
}
