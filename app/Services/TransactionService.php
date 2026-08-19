<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Transaction;

class TransactionService
{
    public static function create(
        Payment $payment,
        string $type,
        string $status
    ): Transaction {

        $existing = Transaction::where('payment_id', $payment->id)
            ->where('type', $type)
            ->where('status', $status)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Transaction::create([

            'payment_id' => $payment->id,

            'booking_id' => $payment->booking_id,

            'customer_id' => $payment->customer_id,

            'provider_id' => $payment->provider_id,

            'type' => $type,

            'amount' => $payment->amount,

            'currency' => $payment->currency,

            'status' => $status,

            'gateway' => $payment->gateway,

            'gateway_transaction_id' => $payment->gateway_transaction_id,

            'metadata' => $payment->metadata,

        ]);
    }
}