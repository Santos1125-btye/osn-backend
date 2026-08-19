<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Wallet;
use App\Models\Notification;
use App\Services\NotificationService;

class WalletService
{
    /**
     * Get or create wallet
     */
    public static function wallet(Provider $provider): Wallet
    {
        return Wallet::firstOrCreate(
            [
                'provider_id' => $provider->id
            ],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
            ]
        );
    }

    /**
     * Move money into pending balance
     */
    public static function addPending(
        Provider $provider,
        float $amount
    ): void {
        $wallet = self::wallet($provider);

        $wallet->increment(
            'pending_balance',
            $amount
        );
    }

    /**
     * Release pending earnings
     *
     * Moves earnings from pending balance
     * into available balance and notifies
     * the provider.
     */
    public static function releasePending(
        Provider $provider,
        float $amount
    ): void {
        $wallet = self::wallet($provider);

        $wallet->decrement(
            'pending_balance',
            $amount
        );

        $wallet->increment(
            'available_balance',
            $amount
        );

        $wallet->increment(
            'total_earned',
            $amount
        );

        /*
        |--------------------------------------------------------------------------
        | Notify provider
        |--------------------------------------------------------------------------
        |
        | NotificationService handles:
        |
        | - In-app notification
        | - FCM push
        | - Queued email
        |
        */

        $provider->loadMissing('user');

        if ($provider->user) {
            NotificationService::send(
                $provider->user->id,
                'Earnings Available',
                '₦' . number_format($amount, 2) .
                    ' has been released from your pending earnings and is now available in your wallet.',
                Notification::TYPE_WALLET,
                [
                    'amount' => $amount,
                    'wallet_id' => $wallet->id,
                    'available_balance' => $wallet->fresh()->available_balance,
                ]
            );
        }
    }
}