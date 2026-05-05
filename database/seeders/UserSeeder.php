<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('12345678');
        $users = [];

        for ($i = 1; $i <= 3; $i++) {
            $users[$i] = User::factory()->create([
                'name' => "Teste Usuário {$i}",
                'email' => "teste{$i}@example.com",
                'password' => $password,
            ]);
        }

        Wallet::query()->create([
            'user_id' => $users[3]->id,
            'balance' => '-300.00',
        ]);
    }
}
