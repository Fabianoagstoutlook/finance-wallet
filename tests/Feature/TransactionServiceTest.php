<?php

namespace Tests\Feature;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Exceptions\InsufficientFundsException;
use App\Services\Exceptions\InvalidTransactionException;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_creates_wallet_and_transaction(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $transaction = $service->deposit($user->id, '10.50');

        $wallet = Wallet::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($wallet);
        $this->assertSame('10.50', (string) $wallet->balance);
        $this->assertSame(TransactionTypeEnum::DEPOSIT, $transaction->type);
        $this->assertSame(TransactionStatusEnum::COMPLETED, $transaction->status);
        $this->assertSame($wallet->id, $transaction->to_wallet_id);
        $this->assertNull($transaction->from_wallet_id);
    }

    public function test_transfer_moves_balance_and_creates_transaction(): void
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $fromUser->id,
            'balance' => '100.00',
        ]);

        $service = new TransactionService();

        $transaction = $service->transfer($fromUser->id, $toUser->id, '25.50');

        $fromWallet = Wallet::query()->where('user_id', $fromUser->id)->first();
        $toWallet = Wallet::query()->where('user_id', $toUser->id)->first();

        $this->assertNotNull($fromWallet);
        $this->assertNotNull($toWallet);
        $this->assertSame('74.50', (string) $fromWallet->balance);
        $this->assertSame('25.50', (string) $toWallet->balance);
        $this->assertSame(TransactionTypeEnum::TRANSFER, $transaction->type);
        $this->assertSame(TransactionStatusEnum::COMPLETED, $transaction->status);
        $this->assertSame($fromWallet->id, $transaction->from_wallet_id);
        $this->assertSame($toWallet->id, $transaction->to_wallet_id);
    }

    public function test_transfer_to_same_user_throws_exception(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $this->expectException(InvalidTransactionException::class);

        $service->transfer($user->id, $user->id, '10.00');
    }

    public function test_transfer_with_insufficient_funds_throws_exception(): void
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $fromUser->id,
            'balance' => '5.00',
        ]);

        $service = new TransactionService();

        $this->expectException(InsufficientFundsException::class);

        $service->transfer($fromUser->id, $toUser->id, '10.00');
    }

    public function test_reverse_deposit_creates_reversal_and_updates_balance(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $deposit = $service->deposit($user->id, '50.00');

        $reversal = $service->reverse($deposit->id);

        $wallet = Wallet::query()->where('user_id', $user->id)->first();
        $deposit->refresh();

        $this->assertSame('0.00', (string) $wallet->balance);
        $this->assertSame(TransactionStatusEnum::REVERSED, $deposit->status);
        $this->assertSame(TransactionTypeEnum::REVERSAL, $reversal->type);
        $this->assertSame($deposit->id, $reversal->reference_id);
        $this->assertSame($wallet->id, $reversal->from_wallet_id);
        $this->assertNull($reversal->to_wallet_id);
    }

    public function test_reverse_transfer_restores_balances(): void
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $fromUser->id,
            'balance' => '40.00',
        ]);

        $service = new TransactionService();

        $transfer = $service->transfer($fromUser->id, $toUser->id, '15.00');
        $reversal = $service->reverse($transfer->id);

        $fromWallet = Wallet::query()->where('user_id', $fromUser->id)->first();
        $toWallet = Wallet::query()->where('user_id', $toUser->id)->first();
        $transfer->refresh();

        $this->assertSame('40.00', (string) $fromWallet->balance);
        $this->assertSame('0.00', (string) $toWallet->balance);
        $this->assertSame(TransactionStatusEnum::REVERSED, $transfer->status);
        $this->assertSame(TransactionTypeEnum::REVERSAL, $reversal->type);
        $this->assertSame($transfer->id, $reversal->reference_id);
        $this->assertSame($toWallet->id, $reversal->from_wallet_id);
        $this->assertSame($fromWallet->id, $reversal->to_wallet_id);
    }

    public function test_reverse_invalid_transaction_throws_exception(): void
    {
        $service = new TransactionService();

        $this->expectException(InvalidTransactionException::class);

        $service->reverse(9999);
    }

    public function test_reverse_already_reversed_transaction_throws_exception(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $deposit = $service->deposit($user->id, '10.00');
        $service->reverse($deposit->id);

        $this->expectException(InvalidTransactionException::class);

        $service->reverse($deposit->id);
    }

    public function test_deposit_normalizes_comma_amount(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $service->deposit($user->id, '7,5');

        $wallet = Wallet::query()->where('user_id', $user->id)->first();

        $this->assertSame('7.50', (string) $wallet->balance);
    }

    public function test_deposit_is_idempotent_by_key(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $first = $service->deposit($user->id, '10.00', 'deposit-key');
        $second = $service->deposit($user->id, '10.00', 'deposit-key');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Transaction::query()->where('idempotency_key', 'deposit-key')->count());
    }

    public function test_transfer_is_idempotent_by_key(): void
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $fromUser->id,
            'balance' => '20.00',
        ]);

        $service = new TransactionService();

        $first = $service->transfer($fromUser->id, $toUser->id, '5.00', 'transfer-key');
        $second = $service->transfer($fromUser->id, $toUser->id, '5.00', 'transfer-key');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Transaction::query()->where('idempotency_key', 'transfer-key')->count());
    }
}
