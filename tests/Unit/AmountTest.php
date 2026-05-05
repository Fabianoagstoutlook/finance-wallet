<?php

namespace Tests\Unit;

use App\Services\Exceptions\InvalidTransactionException;
use App\ValueObjects\Amount;
use PHPUnit\Framework\TestCase;

class AmountTest extends TestCase
{
    public function test_creates_amount_from_integer_string(): void
    {
        $amount = Amount::from('10');

        $this->assertSame('10.00', $amount->value());
    }

    public function test_creates_amount_from_decimal_with_comma(): void
    {
        $amount = Amount::from('7,5');

        $this->assertSame('7.50', $amount->value());
    }

    public function test_rejects_invalid_format(): void
    {
        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('Valor inválido.');

        Amount::from('10,000.50');
    }

    public function test_rejects_zero_or_negative(): void
    {
        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('Valor deve ser maior que zero.');

        Amount::from('0');
    }
}
