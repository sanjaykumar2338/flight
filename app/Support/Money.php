<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;

class Money implements JsonSerializable
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency = 'USD'
    ) {
        if ($this->currency === '') {
            throw new InvalidArgumentException('Currency is required for money values.');
        }
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, strtoupper($currency));
    }

    public static function fromDecimal(float|int|string $value, string $currency = 'USD'): self
    {
        $numeric = is_string($value) ? (float) $value : (float) $value;

        return new self((int) round($numeric * 100), strtoupper($currency));
    }

    public static function fromMinor(int $amount, string $currency = 'USD'): self
    {
        return new self($amount, strtoupper($currency));
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function toFloat(): float
    {
        return round($this->amount / 100, 2);
    }

    public function formatted(int $decimals = 2): string
    {
        return number_format($this->amount / 100, $decimals, '.', '');
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    public function percentage(float $percent): self
    {
        return new self((int) round($this->amount * $percent / 100), $this->currency);
    }

    public function absolute(): self
    {
        return new self(abs($this->amount), $this->currency);
    }

    public function clampMin(self $minimum): self
    {
        $this->assertSameCurrency($minimum);

        return $this->amount < $minimum->amount ? $minimum : $this;
    }

    public function max(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount ? $this : $other;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->formatted(),
            'currency' => $this->currency,
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currencies must match.');
        }
    }
}
