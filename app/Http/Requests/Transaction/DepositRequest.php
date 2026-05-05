<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\d+(?:[\.,]\d{1,2})?$/'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
