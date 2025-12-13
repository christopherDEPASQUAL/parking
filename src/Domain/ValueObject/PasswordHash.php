<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

final class PasswordHash
{
    private string $hashedValue;

    private function __construct(string $hashedValue)
    {
        $this->hashedValue = $hashedValue;
    }

    public static function fromPlainText(string $plainPassword): self
    {
        if (strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit avoir 8 caractères minimum');
        }

        return new self(password_hash($plainPassword, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function getHash(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }
}