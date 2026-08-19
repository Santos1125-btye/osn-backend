<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'bank_code' => 'required|string|max:20',

            'account_number' => 'required|digits:10',

            'is_default' => 'boolean',

            'bank_name' => ['required', 'string'],

        ];
    }
}