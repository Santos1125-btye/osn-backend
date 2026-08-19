<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'provider_bank_account_id'
                => 'required|exists:provider_bank_accounts,id',

            'amount'
                => 'required|numeric|min:1000',

        ];
    }
}