<?php

namespace App\Enums;

enum TransactionStatusEnum: int
{
    case COMPLETED = 1;
    case REVERSED = 2;

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Concluída',
            self::REVERSED => 'Revertida',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isReversed(): bool
    {
        return $this === self::REVERSED;
    }
}