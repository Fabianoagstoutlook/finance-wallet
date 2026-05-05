<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BalanceZeroMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Wallet $wallet
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Saldo zerado na carteira')
            ->markdown('emails.balance-zero');
    }
}
