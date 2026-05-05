<?php

namespace Tests\Feature;

use App\Mail\BalanceZeroMail;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WalletObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_email_when_balance_reaches_zero(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => '10.00',
        ]);

        $wallet->update([
            'balance' => '0.00',
        ]);

        Mail::assertSent(BalanceZeroMail::class, function (BalanceZeroMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }
}
