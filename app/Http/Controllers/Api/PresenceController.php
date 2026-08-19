<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PresenceController extends Controller
{
    /**
     * Update user's online status.
     */
    public function heartbeat()
    {
        Cache::put(
            'user-online-' . Auth::id(),
            now(),
            now()->addMinutes(2)
        );

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Check user's online status.
     */
    public function status($userId)
    {
        $lastSeen = Cache::get(
            'user-online-' . $userId
        );

        return response()->json([

            'online' => $lastSeen
                ? now()->diffInMinutes($lastSeen) < 2
                : false,

            'last_seen' => $lastSeen,

        ]);
    }
}