<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index()
    {
        $addresses = Address::where(
            'user_id',
            Auth::id()
        )
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Store a newly created address.
     */
    public function store(AddressRequest $request)
    {
        if ($request->boolean('is_default')) {
            Address::where(
                'user_id',
                Auth::id()
            )->update([
                'is_default' => false,
            ]);
        }

        $address = Address::create([
            'user_id' => Auth::id(),
            'label' => $request->label,
            'address_line' => $request->address_line,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country ?? 'Nigeria',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_default' => $request->boolean('is_default'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data' => $address,
        ], 201);
    }

    /**
     * Display the specified address.
     */
    public function show(string $id)
    {
        $address = Address::where(
            'user_id',
            Auth::id()
        )->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $address,
        ]);
    }

    /**
     * Update the specified address.
     */
    public function update(
        AddressRequest $request,
        string $id
    ) {
        $address = Address::where(
            'user_id',
            Auth::id()
        )->findOrFail($id);

        // If this address is being made default,
        // remove default from all other addresses.
        if ($request->boolean('is_default')) {
            Address::where(
                'user_id',
                Auth::id()
            )
                ->where('id', '!=', $address->id)
                ->update([
                    'is_default' => false,
                ]);
        }

        $address->update([
            'label' => $request->label,
            'address_line' => $request->address_line,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country ?? 'Nigeria',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_default' => $request->boolean('is_default'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => $address->fresh(),
        ]);
    }

    /**
     * Remove the specified address.
     */
    public function destroy(string $id)
    {
        $address = Address::where(
            'user_id',
            Auth::id()
        )->findOrFail($id);

        $wasDefault = $address->is_default;

        $address->delete();

        // If the deleted address was the default,
        // automatically make the newest remaining
        // address the default.
        if ($wasDefault) {
            $newDefault = Address::where(
                'user_id',
                Auth::id()
            )
                ->latest()
                ->first();

            if ($newDefault) {
                $newDefault->update([
                    'is_default' => true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }
}