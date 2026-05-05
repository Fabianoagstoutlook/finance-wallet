<?php

namespace Tests\Feature;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.view'));

        $response->assertOk();
        $response->assertViewIs('transaction.index');
    }

    public function test_can_deposit_via_controller(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('transactions.deposit'), [
                'amount' => '10.00',
            ]);

        $response->assertCreated();

        $wallet = Wallet::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($wallet);
        $this->assertSame('10.00', (string) $wallet->balance);
        $this->assertDatabaseHas('transactions', [
            'type' => TransactionTypeEnum::DEPOSIT->value,
            'status' => TransactionStatusEnum::COMPLETED->value,
        ]);
    }

    public function test_can_transfer_via_controller(): void
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $fromUser->id,
            'balance' => '20.00',
        ]);

        $response = $this
            ->actingAs($fromUser)
            ->postJson(route('transactions.transfer'), [
                'to_user_id' => $toUser->id,
                'amount' => '5.00',
            ]);

        $response->assertCreated();

        $fromWallet = Wallet::query()->where('user_id', $fromUser->id)->first();
        $toWallet = Wallet::query()->where('user_id', $toUser->id)->first();

        $this->assertSame('15.00', (string) $fromWallet->balance);
        $this->assertSame('5.00', (string) $toWallet->balance);
    }

    public function test_can_reverse_via_controller(): void
    {
        $user = User::factory()->create();
        $service = new TransactionService();

        $deposit = $service->deposit($user->id, '12.00');

        $response = $this
            ->actingAs($user)
            ->postJson(route('transactions.reverse'), [
                'transaction_id' => $deposit->id,
            ]);

        $response->assertCreated();

        $deposit->refresh();

        $this->assertSame(TransactionStatusEnum::REVERSED, $deposit->status);
    }
}
