<?php

namespace App\Observers;

use App\Mail\BalanceZeroMail;
use App\Models\Wallet;
use Illuminate\Support\Facades\Mail;

class WalletObserver
{
    public function updated(Wallet $wallet): void
    {
        if (! $wallet->wasChanged('balance')) {
            return;
        }

        $newBalance = (string) $wallet->balance;
        $oldBalance = (string) $wallet->getOriginal('balance');

        if (bccomp($newBalance, '0.00', 2) !== 0) {
            return;
        }

        if (bccomp($oldBalance, '0.00', 2) === 0) {
            return;
        }

        $user = $wallet->user;

        if (! $user || ! $user->email) {
            return;
        }

        Mail::to($user->email)->send(new BalanceZeroMail($user, $wallet));
    }
}
