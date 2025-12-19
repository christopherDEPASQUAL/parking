<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Infrastructure\Persistence\Json\JsonUserRepository;

/**
 * Alias concret qui dÇ¸finit un chemin par dÇ¸faut dans /storage pour le driver JSON.
 */
final class UserRepositoryFile extends JsonUserRepository
{
    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('JSON_USER_STORAGE') ?: 'storage/users.json');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 5) . '/' . ltrim($resolved, '/\\');
        }
        parent::__construct($resolved);
    }
}
