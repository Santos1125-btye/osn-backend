<?php

namespace App\Services;

use App\Models\PaymentLog;

class PaymentLogger
{
    public static function log(
        int $paymentId,
        string $event,
        string $status,
        array $request = [],
        array $response = [],
        ?string $message = null
    ): void {

        PaymentLog::create([
            'payment_id' => $paymentId,
            'event' => $event,
            'status' => $status,
            'request_payload' => $request,
            'response_payload' => $response,
            'message' => $message,
        ]);
    }
}