<?php declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\Security\TokenBlacklistInterface;

final class FileTokenBlacklist implements TokenBlacklistInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('JWT_BLACKLIST_STORAGE') ?: 'storage/jwt_blacklist.json');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 3) . '/' . ltrim($resolved, '/\\');
        }

        $this->filePath = $resolved;
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_file($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function revoke(string $token, int $expiresAt): void
    {
        $data = $this->readAll();
        $data[$this->hash($token)] = $expiresAt;
        $this->persist($data);
    }

    public function isRevoked(string $token): bool
    {
        $data = $this->readAll();
        $now = time();
        $changed = false;

        foreach ($data as $hash => $expiresAt) {
            if ((int) $expiresAt <= $now) {
                unset($data[$hash]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->persist($data);
        }

        return isset($data[$this->hash($token)]);
    }

    private function readAll(): array
    {
        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
