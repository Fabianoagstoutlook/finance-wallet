<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.view');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.view');
    Route::post('/transactions/deposit', [TransactionController::class, 'deposit'])->name('transactions.deposit');
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
    Route::post('/transactions/withdraw', [TransactionController::class, 'withdraw'])->name('transactions.withdraw');
    Route::post('/transactions/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
});

require __DIR__.'/auth.php';
