<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('wallet.view'));

        $response->assertOk();
        $response->assertViewIs('wallet.index');
        $response->assertViewHas('balance');
        $response->assertViewHas('statement');
    }
}
