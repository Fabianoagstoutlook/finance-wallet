<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('status')->default(1);
            $table->decimal('amount', 15, 2);
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('reference_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['from_wallet_id', 'to_wallet_id']);
            $table->index('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
