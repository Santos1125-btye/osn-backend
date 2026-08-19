<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentLogger;
use Illuminate\Http\Request;
use App\Services\TransactionService;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify Paystack Signature
        $signature = $request->header('x-paystack-signature');

        $computed = hash_hmac(
            'sha512',
            $request->getContent(),
            config('services.paystack.secret_key')
        );

        if ($signature !== $computed) {
            return response()->json([
                'message' => 'Invalid signature.'
            ], 401);
        }

        $payload = $request->all();

        if (($payload['event'] ?? null) !== 'charge.success') {
            return response()->json([
                'message' => 'Event ignored.'
            ]);
        }

        $reference = $payload['data']['reference'];

        $payment = Payment::where(
            'payment_reference',
            $reference
        )->first();

        if (!$payment) {

            return response()->json([
                'message' => 'Payment not found.'
            ], 404);

        }

        // Already processed (Idempotency)
        if ($payment->status === 'successful') {

            PaymentLogger::log(
                $payment->id,
                'webhook',
                'success',
                [],
                $payload,
                'Duplicate webhook ignored.'
            );

            return response()->json([
                'message' => 'Already processed.'
            ]);
        }

        $payment->update([

            'status' => 'successful',

            'gateway_reference' =>
                $payload['data']['reference'],

            'gateway_transaction_id' =>
                $payload['data']['id'],

            'payment_method' =>
                $payload['data']['channel'] ?? null,

            'authorization_code' =>
                $payload['data']['authorization']['authorization_code'] ?? null,

            'gateway_response' =>
                $payload['data']['gateway_response'] ?? null,

            'metadata' =>
                $payload['data'],

            'paid_at' =>
                now(),

        ]);

        $payment->booking()->update([
            'payment_status' => 'paid'
        ]);

        TransactionService::create(
            $payment->fresh(),
            'payment',
            'successful'
        );

        PaymentLogger::log(
            $payment->id,
            'webhook',
            'success',
            [],
            $payload,
            'Webhook processed successfully.'
        );

        return response()->json([
            'message' => 'OK'
        ]);
    }
}