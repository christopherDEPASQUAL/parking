<?php declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Application\Port\Services\PdfGeneratorInterface;

final class DompdfGenerator implements PdfGeneratorInterface
{
    public function generate(string $html, string $filename): ?string
    {
        $outputPath = __DIR__ . '/../../../../storage/pdf/' . $filename;
        $dir = dirname($outputPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($outputPath, $html);
        
        return $outputPath;
    }
}

{
    public function generate(string $html, string $filename): ?string
    {
        $outputPath = __DIR__ . '/../../../../storage/pdf/' . $filename;
        $dir = dirname($outputPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($outputPath, $html);
        
        return $outputPath;
    }
}

{
    public function generate(string $html, string $filename): ?string
    {
        $outputPath = __DIR__ . '/../../../../storage/pdf/' . $filename;
        $dir = dirname($outputPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($outputPath, $html);
        
        return $outputPath;
    }
}
