<?php

namespace App\Services;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Exceptions\InsufficientFundsException;
use App\Services\Exceptions\InvalidTransactionException;
use App\ValueObjects\Amount;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function deposit(int $userId, string|int $amount, ?string $idempotencyKey = null): Transaction
    {
        $normalizedAmount = Amount::from($amount);

        return DB::transaction(function () use ($userId, $normalizedAmount, $idempotencyKey): Transaction {
            $existing = $this->getIdempotentTransaction($idempotencyKey);

            if ($existing) {
                return $existing;
            }

            $wallet = $this->getOrCreateWalletForUpdate($userId);
            $wallet->balance = $this->add($wallet->balance, $normalizedAmount->value());
            $wallet->save();

            return $this->createTransactionWithIdempotency([
                'type' => TransactionTypeEnum::DEPOSIT,
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => $normalizedAmount->value(),
                'from_wallet_id' => null,
                'to_wallet_id' => $wallet->id,
                'reference_id' => null,
                'idempotency_key' => $idempotencyKey,
            ], $idempotencyKey);
        });
    }

    public function transfer(int $fromUserId, int $toUserId, string|int $amount, ?string $idempotencyKey = null): Transaction
    {
        if ($fromUserId === $toUserId) {
            throw new InvalidTransactionException('Não é possível transferir para o mesmo usuário.');
        }

        $normalizedAmount = Amount::from($amount);

        return DB::transaction(function () use ($fromUserId, $toUserId, $normalizedAmount, $idempotencyKey): Transaction {
            $existing = $this->getIdempotentTransaction($idempotencyKey);

            if ($existing) {
                return $existing;
            }

            $wallets = $this->getOrCreateWalletsForUpdate($fromUserId, $toUserId);
            $fromWallet = $wallets[$fromUserId] ?? null;
            $toWallet = $wallets[$toUserId] ?? null;

            if (!$fromWallet || !$toWallet) {
                throw new InvalidTransactionException('Carteira não encontrada.');
            }

            if (bccomp((string) $fromWallet->balance, $normalizedAmount->value(), 2) < 0) {
                throw new InsufficientFundsException('Saldo insuficiente.');
            }

            $fromWallet->balance = $this->sub($fromWallet->balance, $normalizedAmount->value());
            $toWallet->balance = $this->add($toWallet->balance, $normalizedAmount->value());

            $fromWallet->save();
            $toWallet->save();

            return $this->createTransactionWithIdempotency([
                'type' => TransactionTypeEnum::TRANSFER,
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => $normalizedAmount->value(),
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'reference_id' => null,
                'idempotency_key' => $idempotencyKey,
            ], $idempotencyKey);
        });
    }

    public function withdraw(int $userId, string|int $amount, ?string $idempotencyKey = null): Transaction
    {
        $normalizedAmount = Amount::from($amount);

        return DB::transaction(function () use ($userId, $normalizedAmount, $idempotencyKey): Transaction {
            $existing = $this->getIdempotentTransaction($idempotencyKey);

            if ($existing) {
                return $existing;
            }

            $wallet = $this->getOrCreateWalletForUpdate($userId);

            if (bccomp((string) $wallet->balance, $normalizedAmount->value(), 2) < 0) {
                throw new InsufficientFundsException('Saldo insuficiente.');
            }

            $wallet->balance = $this->sub($wallet->balance, $normalizedAmount->value());
            $wallet->save();

            return $this->createTransactionWithIdempotency([
                'type' => TransactionTypeEnum::WITHDRAW,
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => $normalizedAmount->value(),
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => null,
                'reference_id' => null,
                'idempotency_key' => $idempotencyKey,
            ], $idempotencyKey);
        });
    }

    public function reverse(int $transactionId, ?string $idempotencyKey = null): Transaction
    {
        return DB::transaction(function () use ($transactionId, $idempotencyKey): Transaction {
            $existing = $this->getIdempotentTransaction($idempotencyKey);

            if ($existing) {
                return $existing;
            }

            $transaction = Transaction::query()->lockForUpdate()->find($transactionId);

            if (!$transaction) {
                throw new InvalidTransactionException('Transação não encontrada.');
            }

            if ($transaction->status === TransactionStatusEnum::REVERSED) {
                throw new InvalidTransactionException('Transação já revertida.');
            }

            if ($transaction->type === TransactionTypeEnum::DEPOSIT) {
                return $this->reverseDeposit($transaction, $idempotencyKey);
            }

            if ($transaction->type === TransactionTypeEnum::TRANSFER) {
                return $this->reverseTransfer($transaction, $idempotencyKey);
            }

            if ($transaction->type === TransactionTypeEnum::WITHDRAW) {
                return $this->reverseWithdraw($transaction, $idempotencyKey);
            }

            throw new InvalidTransactionException('Tipo de transação não suportado.');
        });
    }

    private function reverseDeposit(Transaction $transaction, ?string $idempotencyKey): Transaction
    {
        $toWallet = $this->getWalletForUpdate($transaction->to_wallet_id);

        if (!$toWallet) {
            throw new InvalidTransactionException('Carteira não encontrada para reversão de depósito.');
        }

        $amount = (string) $transaction->amount;

        $toWallet->balance = $this->sub($toWallet->balance, $amount);
        $toWallet->save();

        $reversal = $this->createTransactionWithIdempotency([
            'type' => TransactionTypeEnum::REVERSAL,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => $amount,
            'from_wallet_id' => $toWallet->id,
            'to_wallet_id' => null,
            'reference_id' => $transaction->id,
            'idempotency_key' => $idempotencyKey,
        ], $idempotencyKey);

        $transaction->status = TransactionStatusEnum::REVERSED;
        $transaction->save();

        return $reversal;
    }

    private function reverseTransfer(Transaction $transaction, ?string $idempotencyKey): Transaction
    {
        $wallets = $this->getWalletsForUpdateByWalletIds([
            $transaction->from_wallet_id,
            $transaction->to_wallet_id,
        ]);

        $fromWallet = $wallets[$transaction->from_wallet_id] ?? null;
        $toWallet = $wallets[$transaction->to_wallet_id] ?? null;

        if (!$fromWallet || !$toWallet) {
            throw new InvalidTransactionException('Carteira não encontrada para reversão de transferência.');
        }

        $amount = (string) $transaction->amount;

        $fromWallet->balance = $this->add($fromWallet->balance, $amount);
        $toWallet->balance = $this->sub($toWallet->balance, $amount);

        $fromWallet->save();
        $toWallet->save();

        $reversal = $this->createTransactionWithIdempotency([
            'type' => TransactionTypeEnum::REVERSAL,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => $amount,
            'from_wallet_id' => $toWallet->id,
            'to_wallet_id' => $fromWallet->id,
            'reference_id' => $transaction->id,
            'idempotency_key' => $idempotencyKey,
        ], $idempotencyKey);

        $transaction->status = TransactionStatusEnum::REVERSED;
        $transaction->save();

        return $reversal;
    }

    private function reverseWithdraw(Transaction $transaction, ?string $idempotencyKey): Transaction
    {
        $fromWallet = $this->getWalletForUpdate($transaction->from_wallet_id);

        if (!$fromWallet) {
            throw new InvalidTransactionException('Carteira não encontrada para reversão de saque.');
        }

        $amount = (string) $transaction->amount;

        $fromWallet->balance = $this->add($fromWallet->balance, $amount);
        $fromWallet->save();

        $reversal = $this->createTransactionWithIdempotency([
            'type' => TransactionTypeEnum::REVERSAL,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => $amount,
            'from_wallet_id' => null,
            'to_wallet_id' => $fromWallet->id,
            'reference_id' => $transaction->id,
            'idempotency_key' => $idempotencyKey,
        ], $idempotencyKey);

        $transaction->status = TransactionStatusEnum::REVERSED;
        $transaction->save();

        return $reversal;
    }

    private function getOrCreateWalletsForUpdate(int $fromUserId, int $toUserId): array
    {
        $userIds = [$fromUserId, $toUserId];

        $users = User::query()->whereIn('id', $userIds)->pluck('id')->all();

        if (count($users) !== 2) {
            throw new InvalidTransactionException('Usuário não encontrado.');
        }

        $wallets = $this->getWalletsForUpdateByUserIds($userIds);
        $missing = array_diff($userIds, $wallets->keys()->all());

        if (!$missing) {
            return $wallets->all();
        }

        foreach ($missing as $userId) {
            try {
                Wallet::query()->create([
                    'user_id' => $userId,
                    'balance' => '0.00',
                ]);
            } catch (QueryException) {
                // Ignore race; re-fetch below.
            }
        }

        $wallets = $this->getWalletsForUpdateByUserIds($userIds);

        return $wallets->all();
    }

    private function getOrCreateWalletForUpdate(int $userId): Wallet
    {
        $wallet = Wallet::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        $user = User::query()->find($userId);

        if (!$user) {
            throw new InvalidTransactionException('Usuário não encontrado.');
        }

        try {
            return Wallet::query()->create([
                'user_id' => $userId,
                'balance' => '0.00',
            ]);
        } catch (QueryException $exception) {
            $wallet = Wallet::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                return $wallet;
            }

            throw $exception;
        }
    }

    private function getWalletForUpdate(?int $walletId): ?Wallet
    {
        if (!$walletId) {
            return null;
        }

        return Wallet::query()->lockForUpdate()->find($walletId);
    }

    private function getWalletsForUpdateByUserIds(array $userIds)
    {
        return Wallet::query()
            ->whereIn('user_id', $userIds)
            ->orderBy('user_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('user_id');
    }

    private function getWalletsForUpdateByWalletIds(array $walletIds)
    {
        return Wallet::query()
            ->whereIn('id', $walletIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function getIdempotentTransaction(?string $idempotencyKey): ?Transaction
    {
        if (!$idempotencyKey) {
            return null;
        }

        return Transaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    private function createTransactionWithIdempotency(array $payload, ?string $idempotencyKey): Transaction
    {
        try {
            return Transaction::query()->create($payload);
        } catch (QueryException $exception) {
            $existing = $this->getIdempotentTransaction($idempotencyKey);

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function add(string $left, string $right): string
    {
        return bcadd($left, $right, 2);
    }

    private function sub(string $left, string $right): string
    {
        return bcsub($left, $right, 2);
    }
}
