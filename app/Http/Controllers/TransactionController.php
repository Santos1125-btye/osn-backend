<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Customer payment history.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $filter = strtolower(
            $request->query('filter', 'all')
        );

        /*
        |--------------------------------------------------------------------------
        | Monthly Summary
        |--------------------------------------------------------------------------
        */

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Successful payments come from transactions.
        $successfulPayments = Transaction::query()
            ->where('customer_id', $user->id)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where(
                'status',
                Transaction::STATUS_SUCCESSFUL
            )
            ->whereBetween(
                'transaction_date',
                [$monthStart, $monthEnd]
            );

        // Pending payments come from unpaid bookings.
        $pendingBookings = Booking::query()
            ->where('customer_id', $user->id)
            ->where('payment_status', 'pending')
            ->whereBetween(
                'created_at',
                [$monthStart, $monthEnd]
            );

        // Refunds come from transactions.
        $successfulRefunds = Transaction::query()
            ->where('customer_id', $user->id)
            ->where('type', Transaction::TYPE_REFUND)
            ->where(
                'status',
                Transaction::STATUS_SUCCESSFUL
            )
            ->whereBetween(
                'transaction_date',
                [$monthStart, $monthEnd]
            );

        $summary = [
            'total_paid' => (float) $successfulPayments->sum('amount'),

            'successful_count' =>
                $successfulPayments->count(),

            'pending_amount' =>
                (float) $pendingBookings->sum('total_amount'),

            'pending_count' =>
                $pendingBookings->count(),

            'refunded_amount' =>
                (float) $successfulRefunds->sum('amount'),

            'refunded_count' =>
                $successfulRefunds->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Pending Payments
        |--------------------------------------------------------------------------
        |
        | Pending payments are bookings that have not been paid.
        |
        */

        if ($filter === 'pending') {
            $pending = Booking::query()
                ->where('customer_id', $user->id)
                ->where('payment_status', 'pending')
                ->with([
                    'provider',
                    'service',
                ])
                ->latest('created_at')
                ->paginate(15)
                ->through(function ($booking) {
                    return $this->formatPendingBooking(
                        $booking
                    );
                });

            return response()->json([
                'success' => true,

                'data' => [
                    'summary' => $summary,

                    'transactions' => $pending,
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction History
        |--------------------------------------------------------------------------
        */

        $query = Transaction::query()
            ->where('customer_id', $user->id)
            ->whereIn('type', [
                Transaction::TYPE_PAYMENT,
                Transaction::TYPE_REFUND,
            ])
            ->with([
                'booking.service',
                'provider',
            ])
            ->orderByDesc('transaction_date');

        switch ($filter) {
            case 'paid':

                $query
                    ->where(
                        'type',
                        Transaction::TYPE_PAYMENT
                    )
                    ->where(
                        'status',
                        Transaction::STATUS_SUCCESSFUL
                    );

                break;

            case 'failed':

                $query
                    ->where(
                        'type',
                        Transaction::TYPE_PAYMENT
                    )
                    ->whereIn('status', [
                        Transaction::STATUS_FAILED,
                        Transaction::STATUS_CANCELLED,
                    ]);

                break;

            case 'refunded':

                $query
                    ->where(
                        'type',
                        Transaction::TYPE_REFUND
                    )
                    ->where(
                        'status',
                        Transaction::STATUS_SUCCESSFUL
                    );

                break;
        }

        $transactions = $query
            ->paginate(15)
            ->through(function ($transaction) {
                return $this->formatTransaction(
                    $transaction
                );
            });

        return response()->json([
            'success' => true,

            'data' => [
                'summary' => $summary,

                'transactions' => $transactions,
            ],
        ]);
    }

    /**
     * Format a real transaction.
     */
    private function formatTransaction(
        Transaction $transaction
    ): array {
        $isRefund =
            $transaction->type === Transaction::TYPE_REFUND;

        $status = match (true) {
            $isRefund &&
            $transaction->status === Transaction::STATUS_SUCCESSFUL
                => 'refunded',

            $transaction->status === Transaction::STATUS_SUCCESSFUL
                => 'paid',

            in_array($transaction->status, [
                Transaction::STATUS_FAILED,
                Transaction::STATUS_CANCELLED,
            ], true)
                => 'failed',

            default => $transaction->status,
        };

        $providerName =
            $transaction->provider?->business_name
            ?? 'Provider';

        $serviceName =
            $transaction->booking?->service?->service_name
            ?? 'Service Payment';

        return [
            'id' =>
                $transaction->id,

            'reference' =>
                $transaction->transaction_reference,

            'booking_id' =>
                $transaction->booking_id,

            'provider' =>
                $providerName,

            'service' =>
                $serviceName,

            'amount' =>
                (float) $transaction->amount,

            'currency' =>
                $transaction->currency ?? 'NGN',

            'type' =>
                $transaction->type,

            'status' =>
                $status,

            'gateway' =>
                $transaction->gateway,

            'gateway_transaction_id' =>
                $transaction->gateway_transaction_id,

            'date' =>
                $transaction->transaction_date?->toISOString(),
        ];
    }

    /**
     * Format an unpaid booking as a pending payment.
     */
    private function formatPendingBooking(
        Booking $booking
    ): array {
        return [
            'id' =>
                $booking->id,

            'reference' =>
                $booking->booking_reference,

            'booking_id' =>
                $booking->id,

            'provider' =>
                $booking->provider?->business_name
                ?? 'Provider',

            'service' =>
                $booking->service?->service_name
                ?? 'Service',

            'amount' =>
                (float) $booking->total_amount,

            'currency' =>
                'NGN',

            'type' =>
                'payment',

            'status' =>
                'pending',

            'gateway' =>
                null,

            'gateway_transaction_id' =>
                null,

            'date' =>
                $booking->created_at?->toISOString(),
        ];
    }
}