<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\DepositRequest;
use App\Http\Requests\Transaction\ReverseRequest;
use App\Http\Requests\Transaction\TransferRequest;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\Exceptions\InsufficientFundsException;
use App\Services\Exceptions\InvalidTransactionException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('transaction.index');
    }

    public function deposit(DepositRequest $request, TransactionService $service): JsonResponse
    {
        try {
            $transaction = $service->deposit(
                $request->user()->id,
                $request->input('amount'),
                $request->input('idempotency_key')
            );

            return response()->json(['transaction' => $transaction], 201);
        } catch (InvalidTransactionException|InsufficientFundsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function transfer(TransferRequest $request, TransactionService $service): JsonResponse
    {
        try {
            $toUserId = $request->input('to_user_id');

            if (!$toUserId) {
                $toUserId = (int) User::query()
                    ->where('email', $request->input('to_email'))
                    ->value('id');
            }

            if (!$toUserId) {
                return response()->json(['message' => 'Usuário não encontrado.'], 422);
            }

            $transaction = $service->transfer(
                $request->user()->id,
                (int) $toUserId,
                $request->input('amount'),
                $request->input('idempotency_key')
            );

            return response()->json(['transaction' => $transaction], 201);
        } catch (InvalidTransactionException|InsufficientFundsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function withdraw(DepositRequest $request, TransactionService $service): JsonResponse
    {
        try {
            $transaction = $service->withdraw(
                $request->user()->id,
                $request->input('amount'),
                $request->input('idempotency_key')
            );

            return response()->json(['transaction' => $transaction], 201);
        } catch (InvalidTransactionException|InsufficientFundsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function reverse(ReverseRequest $request, TransactionService $service): JsonResponse
    {
        try {
            $transaction = $service->reverse(
                (int) $request->input('transaction_id'),
                $request->input('idempotency_key')
            );

            return response()->json(['transaction' => $transaction], 201);
        } catch (InvalidTransactionException|InsufficientFundsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
