<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

final class PasswordHash
{
    private string $hash;

    private function __construct(string $hash)
    {
        $hash = trim($hash);
        if ($hash === '') {
            throw new \InvalidArgumentException('Password hash cannot be empty.');
        }

        $this->hash = $hash;
    }

    public static function fromPlainText(string $plainPassword): self
    {
        if (strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters');
        }

        return new self(password_hash($plainPassword, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hash);
    }

    public function equals(PasswordHash $other): bool
    {
        return hash_equals($this->hash, $other->hash);
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}