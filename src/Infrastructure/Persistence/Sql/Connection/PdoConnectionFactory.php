<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Connection;

use PDO;

/**
 * Provides configured PDO connection instances to SQL repositories.
 *
 * The factory reads DSN/credentials from environment variables to avoid
 * coupling infrastructure concerns to domain/application layers.
 */
final class PdoConnectionFactory
{
    /** @var array<bool, PDO> */
    private array $cache = [];

    public function create(bool $useTestDatabase = false): PDO
    {
        if (isset($this->cache[$useTestDatabase])) {
            return $this->cache[$useTestDatabase];
        }

        $dsn = getenv($useTestDatabase ? 'TEST_DB_DSN' : 'APP_DB_DSN');
        $user = getenv($useTestDatabase ? 'TEST_DB_USER' : 'APP_DB_USER');
        $password = getenv($useTestDatabase ? 'TEST_DB_PASSWORD' : 'APP_DB_PASSWORD');

        if (!$dsn) {
            $host = getenv('APP_DB_HOST') ?: 'localhost';
            $port = getenv($useTestDatabase ? 'TEST_DB_PORT' : 'APP_DB_PORT') ?: '3306';
            $dbname = getenv($useTestDatabase ? 'TEST_DB_NAME' : 'APP_DB_NAME') ?: 'parking';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
        }

        $pdo = new PDO($dsn, $user ?: null, $password ?: null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->cache[$useTestDatabase] = $pdo;

        return $pdo;
    }
}
