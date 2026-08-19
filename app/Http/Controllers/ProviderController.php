<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProviderController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'providers' => Provider::where('status', true)
                ->with('user')
                ->get(),
        ]);
    }

    public function show(Provider $provider)
    {
        return response()->json([
            'success' => true,
            'provider' => $provider->load('user'),
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'provider' => $request->user()->provider,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'bio' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = Provider::create([
            'user_id' => $request->user()->id,
            'business_name' => $request->business_name,
            'phone' => $request->phone,
            'bio' => $request->bio,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider profile created successfully.',
            'provider' => $provider,
        ], 201);
    }

    public function update(Request $request)
    {
        $provider = $request->user()->provider;

        $provider->update($request->only([
            'business_name',
            'phone',
            'bio'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Provider profile updated successfully.',
            'provider' => $provider->fresh(),
        ]);
    }
}