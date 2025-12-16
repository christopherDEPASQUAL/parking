<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Money value object (amount + currency) with safe arithmetic policies.
 *
 * Notes:
 *  - Immutable; validates invariants in constructor.
 *  - No I/O or framework dependencies.
 */
final class Money
{
    private int $amountInCents;
    private string $currency;

    private function __construct(int $amountInCents, string $currency)
    {
        if ($currency === '') {
            throw new \InvalidArgumentException('Currency cannot be empty.');
        }

        $this->amountInCents = $amountInCents;
        $this->currency = strtoupper($currency);
    }

    public static function fromCents(int $amountInCents, string $currency = 'EUR'): self
    {
        return new self($amountInCents, $currency);
    }

    public static function fromFloat(float $amount, string $currency = 'EUR'): self
    {
        $cents = (int) round($amount * 100);

        return new self($cents, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        return new self($this->amountInCents * $multiplier, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency
            && $this->amountInCents === $other->amountInCents;
    }

    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function toFloat(): float
    {
        return $this->amountInCents / 100;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch between Money objects.');
        }
    }
}
