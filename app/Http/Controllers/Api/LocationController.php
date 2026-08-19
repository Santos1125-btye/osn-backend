<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class LocationController extends Controller
{
    public function countries()
    {
        return response()->json([
            'success' => true,
            'data' => Country::orderBy('name')->get(),
        ]);
    }

    public function states(Country $country)
    {
        return response()->json([
            'success' => true,
            'data' => State::where('country_id', $country->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function cities(State $state)
    {
        return response()->json([
            'success' => true,
            'data' => City::where('state_id', $state->id)
                ->orderBy('name')
                ->get(),
        ]);
    }
}