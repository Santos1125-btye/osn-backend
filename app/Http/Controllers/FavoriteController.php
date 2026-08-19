<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FavoriteRequest;
use App\Models\Favorite;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Add favorite
     */
    public function store(FavoriteRequest $request)
    {
        $favorite = Favorite::firstOrCreate([
            'customer_id' => Auth::id(),
            'provider_id' => $request->provider_id,
        ]);

        return response()->json([
            'message' => 'Provider added to favorites.',
            'data' => $favorite,
        ]);
    }

    /**
     * Remove favorite
     */
    public function destroy($providerId)
    {
        Favorite::where('customer_id', Auth::id())
            ->where('provider_id', $providerId)
            ->delete();

        return response()->json([
            'message' => 'Provider removed from favorites.'
        ]);
    }

    /**
     * My favorites
     */
    public function index()
    {
        $favorites = Favorite::with([
            'provider.user'
        ])
            ->where('customer_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json($favorites);
    }

    public function isFavorite($providerId)
    {
        $isFavorite = Favorite::where('customer_id', Auth::id())
            ->where('provider_id', $providerId)
            ->exists();

        return response()->json([
            'is_favorite' => $isFavorite
        ]);
    }
}