<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'status',
        'amount',
        'from_wallet_id',
        'to_wallet_id',
        'reference_id',
        'idempotency_key',
    ];

    protected $casts = [
        'type' => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
        'amount' => 'decimal:2',
    ];

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_id');
    }
}
