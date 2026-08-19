<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl = config('services.paystack.payment_url');
    }

    public function initialize($payment, $email)
    {
        $response = Http::withToken($this->secretKey)
            ->post($this->baseUrl.'/transaction/initialize', [
                'email' => $email,
                'amount' => $payment->amount * 100, // Kobo
                'reference' => $payment->payment_reference,
                'currency' => $payment->currency,
            ]);

        return $response->json();
    }

    public function verify($reference)
    {
        $response = Http::withToken($this->secretKey)
            ->get($this->baseUrl."/transaction/verify/{$reference}");

        return $response->json();
    }
}