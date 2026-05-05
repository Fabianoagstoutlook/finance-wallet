<?php

namespace Tests\Feature;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_balance_returns_zero_when_wallet_missing(): void
    {
        $user = User::factory()->create();
        $service = new WalletService();

        $balance = $service->getBalance($user->id);

        $this->assertSame('0.00', $balance);
    }

    public function test_get_statement_returns_empty_when_wallet_missing(): void
    {
        $user = User::factory()->create();
        $service = new WalletService();

        $statement = $service->getStatement($user->id);

        $this->assertSame(0, $statement->total());
        $this->assertCount(0, $statement->items());
    }

    public function test_get_statement_includes_wallet_transactions_in_desc_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => '25.00',
        ]);

        $otherWallet = Wallet::query()->create([
            'user_id' => $otherUser->id,
            'balance' => '10.00',
        ]);

        $first = Transaction::query()->create([
            'type' => TransactionTypeEnum::TRANSFER,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => '5.00',
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $otherWallet->id,
            'reference_id' => null,
            'idempotency_key' => null,
        ]);

        $second = Transaction::query()->create([
            'type' => TransactionTypeEnum::TRANSFER,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => '3.00',
            'from_wallet_id' => $otherWallet->id,
            'to_wallet_id' => $wallet->id,
            'reference_id' => null,
            'idempotency_key' => null,
        ]);

        $service = new WalletService();
        $statement = $service->getStatement($user->id, 10);

        $this->assertSame(2, $statement->total());
        $this->assertSame($second->id, $statement->items()[0]->id);
        $this->assertSame($first->id, $statement->items()[1]->id);
    }
}
