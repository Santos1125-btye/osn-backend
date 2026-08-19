<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * List all addresses
     */
    public function index()
    {
        return response()->json(
            Address::where('user_id', Auth::id())
                ->latest()
                ->get()
        );
    }

    /**
     * Add address
     */
    public function store(AddressRequest $request)
    {
        if ($request->boolean('is_default')) {

            Address::where('user_id', Auth::id())
                ->update([
                    'is_default' => false
                ]);
        }

        $address = Address::create([
            'user_id'      => Auth::id(),
            'label'        => $request->label,
            'address_line' => $request->address_line,
            'city'         => $request->city,
            'state'        => $request->state,
            'country'      => $request->country ?? 'Nigeria',
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'is_default'   => $request->boolean('is_default'),
        ]);

        return response()->json([
            'message' => 'Address added successfully.',
            'data'    => $address
        ], 201);
    }

    /**
     * Update address
     */
    public function update(AddressRequest $request, Address $address)
    {
        abort_if($address->user_id !== Auth::id(), 403);

        if ($request->boolean('is_default')) {

            Address::where('user_id', Auth::id())
                ->update([
                    'is_default' => false
                ]);
        }

        $address->update($request->validated());

        return response()->json([
            'message' => 'Address updated successfully.',
            'data'    => $address
        ]);
    }

    /**
     * Delete address
     */
    public function destroy(Address $address)
    {
        abort_if($address->user_id !== Auth::id(), 403);

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.'
        ]);
    }

    /**
     * Set default address
     */
    public function setDefault(Address $address)
    {
        abort_if($address->user_id !== Auth::id(), 403);

        Address::where('user_id', Auth::id())
            ->update([
                'is_default' => false
            ]);

        $address->update([
            'is_default' => true
        ]);

        return response()->json([
            'message' => 'Default address updated.'
        ]);
    }
}