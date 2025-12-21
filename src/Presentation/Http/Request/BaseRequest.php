<?php declare(strict_types=1);

namespace App\Presentation\Http\Request;

abstract class BaseRequest
{
    protected static function requireString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException($key . ' is required');
        }

        return $value;
    }

    protected static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    protected static function requireInt(array $data, string $key): int
    {
        if (!isset($data[$key])) {
            throw new \InvalidArgumentException($key . ' is required');
        }

        return (int) $data[$key];
    }
}
