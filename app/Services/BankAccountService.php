<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BankAccountService
{
    public static function resolve(
        string $accountNumber,
        string $bankCode
    ): array {

        $response = Http::withToken(
            config('services.paystack.secret_key')
        )->get(
                config('services.paystack.payment_url') .
                '/bank/resolve',
                [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ]
            );

        return $response->json();
    }

    public static function banks(): array
    {
        $response = Http::withToken(
            config('services.paystack.secret_key')
        )->get(
                config('services.paystack.payment_url') . '/bank',
                [
                    'country' => 'nigeria'
                ]
            );

        return $response->json();
    }
}