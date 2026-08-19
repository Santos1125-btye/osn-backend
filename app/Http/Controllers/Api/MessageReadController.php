<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageRead;
use Illuminate\Support\Facades\Auth;

class MessageReadController extends Controller
{
    /**
     * Mark a message as delivered.
     */
    public function delivered(Message $message)
    {
        MessageRead::updateOrCreate(

            [
                'message_id' => $message->id,
                'user_id' => Auth::id(),
            ],

            [
                'delivered_at' => now(),
            ]

        );

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Mark a message as read.
     */
    public function read(Message $message)
    {
        MessageRead::updateOrCreate(

            [
                'message_id' => $message->id,
                'user_id' => Auth::id(),
            ],

            [
                'delivered_at' => now(),
                'read_at' => now(),
            ]

        );

        return response()->json([
            'success' => true,
        ]);
    }
}