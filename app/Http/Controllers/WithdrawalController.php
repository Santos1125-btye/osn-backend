<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalRequest;
use App\Models\Withdrawal;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    /**
     * Wallet summary
     */
    public function wallet()
    {
        return response()->json(
            Auth::user()
                ->provider
                ->wallet
        );
    }

    /**
     * Withdrawal history
     */
    public function index()
    {
        return response()->json(
            Withdrawal::where(
                'provider_id',
                Auth::user()->provider->id
            )
                ->latest()
                ->get()
        );
    }

    /**
     * Request withdrawal
     */
    public function store(
        WithdrawalRequest $request
    ) {
        $provider = Auth::user()->provider;

        $wallet = $provider->wallet;

        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found.'
            ], 422);
        }

        if ($wallet->available_balance < $request->amount) {
            return response()->json([
                'message' => 'Insufficient balance.'
            ], 422);
        }

        $bankAccount = $provider->bankAccounts()
            ->where('id', $request->provider_bank_account_id)
            ->first();

        if (!$bankAccount) {
            return response()->json([
                'message' => 'Invalid bank account.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate pending withdrawal
        |--------------------------------------------------------------------------
        */

        $existingWithdrawal = Withdrawal::where(
            'provider_id',
            $provider->id
        )
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingWithdrawal) {
            return response()->json([
                'message' =>
                    'You already have a withdrawal request being processed.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Create withdrawal
        |--------------------------------------------------------------------------
        */

        $withdrawal = Withdrawal::create([
            'provider_id' => $provider->id,
            'provider_bank_account_id' => $bankAccount->id,
            'amount' => $request->amount,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notification
        |--------------------------------------------------------------------------
        |
        | NotificationService automatically handles:
        |
        | - In-app notification
        | - FCM push
        | - Queued email
        |
        */

        try {
            NotificationService::send(
                Auth::id(),
                'Withdrawal Request Submitted',
                'Your withdrawal request of ₦' .
                    number_format(
                        (float) $withdrawal->amount,
                        2
                    ) .
                    ' has been submitted and is awaiting processing.',
                'wallet',
                [
                    'withdrawal_id' =>
                        (string) $withdrawal->id,

                    'amount' =>
                        (string) $withdrawal->amount,

                    'status' =>
                        $withdrawal->status ?? 'pending',
                ]
            );
        } catch (\Throwable $e) {
            /*
             * Notification failure must never make
             * a successful withdrawal request fail.
             */
            \Illuminate\Support\Facades\Log::error(
                'Withdrawal notification failed.',
                [
                    'withdrawal_id' => $withdrawal->id,
                    'provider_id' => $provider->id,
                    'error' => $e->getMessage(),
                ]
            );
        }

        return response()->json([
            'message' => 'Withdrawal request submitted.',
            'data' => $withdrawal,
        ], 201);
    }
}