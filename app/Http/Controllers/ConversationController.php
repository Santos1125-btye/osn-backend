<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Message;

class ConversationController extends Controller
{
    /**
     * My conversations
     */
    public function index()
    {
        $user = Auth::user();

        $providerId = optional($user->provider)->id;

        $conversations = Conversation::with([

            'customer',

            'provider.user',

            'booking.service',

            'latestMessage.sender',

        ])
            ->where(function ($query) use ($user, $providerId) {

                $query->where('customer_id', $user->id);

                if ($providerId) {
                    $query->orWhere(
                        'provider_id',
                        $providerId
                    );
                }

            })
            ->latest('last_message_at')
            ->paginate(20);

        return response()->json($conversations);
    }

    /**
     * Single conversation
     */
    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        $providerId = optional($user->provider)->id;

        abort_unless(
            $conversation->customer_id == $user->id ||
            $conversation->provider_id == $providerId ||
            $conversation->support_user_id == $user->id,
            403
        );

        return response()->json(

            $conversation->load([

                'customer',

                'provider.user',

                'booking.service',

                'latestMessage',

            ])

        );
    }

    public function bookingConversation(Booking $booking)
    {
        $user = Auth::user();

        abort_unless(
            $user,
            401,
            'Unauthenticated.'
        );

        $providerId = optional($user->provider)->id;

        abort_unless(
            $booking->customer_id === $user->id ||
            $booking->provider_id === $providerId,
            403,
            'You are not authorized to access this booking.'
        );

        // Chat is not available for these statuses
        abort_if(
            in_array($booking->status, [
                'pending',
                'provider_completed',
                'completed',
                'cancelled',
                'rejected',
            ]),
            403,
            'Chat is not available for this booking.'
        );

        // Create conversation if it doesn't exist
        $conversation = Conversation::firstOrCreate(
            [
                'booking_id' => $booking->id,
            ],
            [
                'conversation_type' => 'booking',
                'customer_id' => $booking->customer_id,
                'provider_id' => $booking->provider_id,
                'status' => 'active',
            ]
        );

        return response()->json(
            $conversation->load([
                'customer',
                'provider.user',
                'booking.service',
                'latestMessage.sender',
            ])
        );
    }

    public function supportConversation()
    {
        $user = Auth::user();

        $provider = $user->provider;

        abort_unless($provider, 403, 'Provider profile not found.');

        // Replace this with your actual OSN support user ID.
        $supportUserId = config('app.support_user_id');

        abort_unless(
            $supportUserId,
            500,
            'OSN support user is not configured.'
        );

        $conversation = Conversation::firstOrCreate(
            [
                'conversation_type' => 'support',
                'provider_id' => $provider->id,
            ],
            [
                'booking_id' => null,
                'customer_id' => null,
                'support_user_id' => $supportUserId,
                'status' => 'active',
            ]
        );

        return response()->json(
            $conversation->load([
                'supportUser',
                'provider.user',
                'latestMessage.sender',
            ])
        );
    }

    public function customerSupportConversation()
    {
        $user = Auth::user();

        abort_unless(
            $user,
            401,
            'Unauthenticated.'
        );

        $supportUserId = config('app.support_user_id');

        abort_unless(
            $supportUserId,
            500,
            'OSN support user is not configured.'
        );

        $conversation = Conversation::firstOrCreate(
            [
                'conversation_type' => 'support',
                'customer_id' => $user->id,
            ],
            [
                'booking_id' => null,
                'provider_id' => null,
                'support_user_id' => $supportUserId,
                'status' => 'active',
            ]
        );

        return response()->json(
            $conversation->load([
                'customer',
                'supportUser',
                'latestMessage.sender',
            ])
        );
    }

    /**
     * Unread chat message count
     */
    public function unreadCount()
    {
        $user = Auth::user();

        $providerId = optional($user->provider)->id;

        $count = Message::whereHas('conversation', function ($query) use ($user, $providerId) {
            $query->where(function ($q) use ($user, $providerId) {
                // Customer conversations
                $q->where('customer_id', $user->id);

                // Provider conversations
                if ($providerId) {
                    $q->orWhere('provider_id', $providerId);
                }
            });
        })
            // Never count messages sent by the current user
            ->where('sender_id', '!=', $user->id)

            // Count only messages that this user has NOT read
            ->whereDoesntHave('reads', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereNotNull('read_at');
            })
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }
}