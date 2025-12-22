<?php declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Application\Port\Services\PdfGeneratorInterface;

final class DompdfGenerator implements PdfGeneratorInterface
{
    public function renderToFile(string $html, string $filePath): void
    {
        $dir = \dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($filePath, $html);
    }
}
