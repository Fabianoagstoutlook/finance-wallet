<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(WalletService $service): View
    {
        $user = auth()->user();

        $balance = $service->getBalance($user->id);
        $statement = $service->getStatement($user->id);

        return view('wallet.index', [
            'balance' => $balance,
            'statement' => $statement,
        ]);
    }
}
