<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required_without:to_email', 'integer', 'exists:users,id'],
            'to_email' => ['required_without:to_user_id', 'email', 'exists:users,email'],
            'amount' => ['required', 'regex:/^\d+(?:[\.,]\d{1,2})?$/'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
