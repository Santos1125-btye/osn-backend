<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    /**
     * Customer submits a dispute.
     */
    public function store(Request $request, Booking $booking)
    {
        $user = Auth::user();

        if ($booking->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'provider_completed') {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be confirmed yet.',
            ], 422);
        }

        if ($booking->dispute) {
            return response()->json([
                'success' => false,
                'message' => 'This booking has an active dispute and cannot be completed yet.',
            ], 422);
        }

        if ($booking->dispute) {
            return response()->json([
                'success' => false,
                'message' => 'A dispute already exists for this booking.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'in:service_not_completed,incomplete_service,poor_service_quality,provider_did_not_follow_request,other',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $dispute = Dispute::create([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'provider_id' => $booking->provider_id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispute submitted successfully.',
            'dispute' => $dispute,
        ], 201);
    }
}