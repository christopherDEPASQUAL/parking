<?php declare(strict_types=1);

namespace App\Application\Port\Security;

use App\Domain\ValueObject\PasswordHash;

/**
 * Port pour hacher et verifier les mots de passe.
 */
interface PasswordHasherInterface
{
    public function hash(string $plainPassword): PasswordHash;

    public function verify(string $plainPassword, PasswordHash $hash): bool;
}
