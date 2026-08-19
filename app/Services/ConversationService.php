<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Conversation;
use App\Services\MessageService;
use App\constants\ChatMessages;

class ConversationService
{
    /**
     * Create conversation after provider accepts booking.
     */
    public static function create(Booking $booking): Conversation
    {
        $conversation = Conversation::firstOrCreate(

            [
                'booking_id' => $booking->id,
            ],

            [
                'customer_id' => $booking->customer_id,

                'provider_id' => $booking->provider_id,

                'status' => 'active',
            ]

        );

        if ($conversation->wasRecentlyCreated) {

            MessageService::system(
                $conversation,
                ChatMessages::BOOKING_ACCEPTED
            );

        }

        return $conversation;
    }

    /**
     * Close conversation.
     */
    public static function close(
        Conversation $conversation,
        string $status
    ): void {

        $conversation->update([

            'status' => $status,

            'closed_at' => now(),

        ]);
    }
}