<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WalletService
{
    public function getBalance(int $userId): string
    {
        $wallet = Wallet::query()->where('user_id', $userId)->first();

        if (!$wallet) {
            return '0.00';
        }

        return (string) $wallet->balance;
    }

    public function getStatement(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        $wallet = Wallet::query()->where('user_id', $userId)->first();

        if (!$wallet) {
            return Transaction::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        return Transaction::query()
            ->where('from_wallet_id', $wallet->id)
            ->orWhere('to_wallet_id', $wallet->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
