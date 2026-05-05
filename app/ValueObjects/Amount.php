<?php

namespace App\ValueObjects;

use App\Services\Exceptions\InvalidTransactionException;

final class Amount
{
    private const SCALE = 2;

    private function __construct(private readonly string $value)
    {
    }

    public static function from(string|int $amount): self
    {
        $normalized = self::normalize($amount);

        if (bccomp($normalized, '0.00', self::SCALE) <= 0) {
            throw new InvalidTransactionException('Valor deve ser maior que zero.');
        }

        return new self($normalized);
    }

    public static function zero(): self
    {
        return new self('0.00');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->value, $other->value, self::SCALE));
    }

    public function sub(self $other): self
    {
        return new self(bcsub($this->value, $other->value, self::SCALE));
    }

    public function compare(self $other): int
    {
        return bccomp($this->value, $other->value, self::SCALE);
    }

    private static function normalize(string|int $amount): string
    {
        $normalized = str_replace(',', '.', trim((string) $amount));

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidTransactionException('Valor inválido.');
        }

        return bcadd($normalized, '0', self::SCALE);
    }
}