<?php declare(strict_types=1);

namespace App\Infrastructure\Logging;

final class SimpleLogger
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('APP_LOG_FILE') ?: 'storage/logs/app.log');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 3) . '/' . ltrim($resolved, '/\\');
        }

        $this->filePath = $resolved;
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context): void
    {
        $time = (new \DateTimeImmutable())->format(DATE_ATOM);
        $payload = $context !== [] ? ' ' . json_encode($context) : '';
        file_put_contents($this->filePath, sprintf("[%s] %s %s%s\n", $time, $level, $message, $payload), FILE_APPEND);
    }
}
