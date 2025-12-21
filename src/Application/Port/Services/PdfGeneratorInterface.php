<?php declare(strict_types=1);

namespace App\Application\Port\Services;

/**
 * Port: render PDFs (invoices/receipts).
 *
 * Implementations:
 *  - Infrastructure layer.
 */
interface PdfGeneratorInterface
{
    public function renderToFile(string $html, string $filePath): void;
}
