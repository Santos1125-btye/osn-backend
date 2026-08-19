<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TypingController extends Controller
{
    public function start(
        Conversation $conversation
    ) {
        Cache::put(

            'typing-' .
            $conversation->id .
            '-' .
            Auth::id(),

            true,

            now()->addSeconds(10)

        );

        return response()->json([
            'success' => true
        ]);
    }

    public function stop(
        Conversation $conversation
    ) {
        Cache::forget(

            'typing-' .
            $conversation->id .
            '-' .
            Auth::id()

        );

        return response()->json([
            'success' => true
        ]);
    }

    public function status(
        Conversation $conversation,
        $userId
    ) {
        return response()->json([
            'typing' => Cache::has(
                'typing-' .
                $conversation->id .
                '-' .
                $userId
            )
        ]);
    }
}