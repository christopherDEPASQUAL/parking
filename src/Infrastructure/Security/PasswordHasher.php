<?php declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\Security\PasswordHasherInterface;
use App\Domain\ValueObject\PasswordHash;

final class PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): PasswordHash
    {
        return PasswordHash::fromPlainText($plainPassword);
    }

    public function verify(string $plainPassword, PasswordHash $passwordHash): bool
    {
        return $passwordHash->verify($plainPassword);
    }
}