<?php

namespace App\Enums;

enum TransactionTypeEnum: int
{
    case DEPOSIT = 1;
    case TRANSFER = 2;
    case REVERSAL = 3;
    case WITHDRAW = 4;

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Depósito',
            self::TRANSFER => 'Transferência',
            self::REVERSAL => 'Reversão',
            self::WITHDRAW => 'Saque',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DEPOSIT => 'fa-solid fa-arrow-down',
            self::TRANSFER => 'fa-solid fa-arrows-left-right',
            self::REVERSAL => 'fa-solid fa-rotate-left',
            self::WITHDRAW => 'fa-solid fa-arrow-up',
        };
    }

    public function isDeposit(): bool
    {
        return $this === self::DEPOSIT;
    }

    public function isTransfer(): bool
    {
        return $this === self::TRANSFER;
    }

    public function isWithdraw(): bool
    {
        return $this === self::WITHDRAW;
    }

    public function isReversal(): bool
    {
        return $this === self::REVERSAL;
    }
}