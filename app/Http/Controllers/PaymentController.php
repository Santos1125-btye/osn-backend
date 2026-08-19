<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use App\Services\TransactionService;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Models\BookingTimeline;

class PaymentController extends Controller
{
    protected PaystackService $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    public function initialize(Request $request, Booking $booking)
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Booking already paid.',
            ], 400);
        }

        // Remove previous unsuccessful payment attempt
        $existingPayment = Payment::where(
            'booking_id',
            $booking->id
        )->first();

        if ($existingPayment && $existingPayment->status !== 'successful') {
            $existingPayment->delete();
        }

        // Create a fresh payment attempt
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'provider_id' => $booking->provider_id,
            'amount' => $booking->amount,
            'provider_amount' => $booking->amount,
            'platform_fee' => 0,
        ]);

        $response = $this->paystack->initialize(
            $payment,
            $request->user()->email
        );

        return response()->json($response);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $response = $this->paystack->verify($request->reference);

        if (
            isset($response['data']['status']) &&
            $response['data']['status'] === 'success'
        ) {

            $payment = Payment::where(
                'payment_reference',
                $request->reference
            )->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found.'
                ], 404);
            }

            // Prevent duplicate processing
            if ($payment->status === 'successful') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already verified.',
                    'payment' => $payment,
                ]);
            }

            $payment->update([
                'status' => 'successful',

                'gateway_reference' =>
                    $response['data']['reference'] ?? null,

                'gateway_transaction_id' =>
                    $response['data']['id'] ?? null,

                'payment_method' =>
                    $response['data']['channel'] ?? null,

                'authorization_code' =>
                    $response['data']['authorization']['authorization_code'] ?? null,

                'gateway_response' =>
                    $response['data']['gateway_response'] ?? null,

                'metadata' => $response['data'],

                'paid_at' => now(),
            ]);

            $payment->booking->update([
                'payment_status' => 'paid',
            ]);

            BookingTimeline::create([

                'booking_id' => $payment->booking_id,

                'status' => 'paid',

                'title' => 'Payment Successful',

                'description' => 'Customer completed payment.',

                'created_by' => 'customer',

            ]);

            TransactionService::create(
                $payment->fresh(),
                'payment',
                'successful'
            );

            NotificationService::send(

                $payment->customer_id,

                'Payment Successful',

                'Your payment has been received.',

                Notification::TYPE_PAYMENT,

                [
                    'booking_id' => $payment->booking_id,
                ]

            );

            NotificationService::send(

                $payment->provider->user_id,

                'Payment Received',

                'A customer has successfully paid for a booking.',

                Notification::TYPE_PAYMENT,

                [
                    'booking_id' => $payment->booking_id,
                ]

            );

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'payment' => $payment->fresh(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['data']['gateway_response'] ?? 'Payment verification failed.',
            'response' => $response,
        ], 400);
    }
}