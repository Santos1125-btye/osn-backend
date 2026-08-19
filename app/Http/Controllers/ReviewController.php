<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request)
    {
        $booking = Booking::findOrFail($request->booking_id);

        // Booking belongs to customer
        if ($booking->customer_id != Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        // Booking completed
        if ($booking->status !== 'completed') {
            return response()->json([
                'message' => 'Only completed bookings can be reviewed.'
            ], 422);
        }

        // Payment completed
        if ($booking->payment_status !== 'paid') {
            return response()->json([
                'message' => 'Payment must be completed before leaving a review.'
            ], 422);
        }

        // One review only
        if ($booking->review) {
            return response()->json([
                'message' => 'You have already reviewed this booking.'
            ], 422);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'customer_id' => Auth::id(),
            'provider_id' => $booking->provider_id,
            'service_id' => $booking->service_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $provider = $booking->provider;

        if ($provider && $provider->user_id) {
            $customer = Auth::user();

            NotificationService::send(
                $provider->user_id,
                'New Review Received',
                $customer->full_name
                . ' left you a '
                . $review->rating
                . '-star review.',
                Notification::TYPE_REVIEW,
                [
                    'review_id' => $review->id,
                    'booking_id' => $booking->id,
                    'provider_id' => $provider->id,
                    'service_id' => $review->service_id,
                    'rating' => $review->rating,
                ]
            );
        }

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }

    public function providerReviews($providerId)
    {
        $reviews = Review::with([
            'customer',
            'service',
        ])
            ->where('provider_id', $providerId)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function summary($providerId)
    {
        $reviews = Review::where('provider_id', $providerId);

        return response()->json([
            'average_rating' => round($reviews->avg('rating') ?? 0, 1),
            'total_reviews' => $reviews->count(),

            'rating_breakdown' => [
                5 => (clone $reviews)->where('rating', 5)->count(),
                4 => (clone $reviews)->where('rating', 4)->count(),
                3 => (clone $reviews)->where('rating', 3)->count(),
                2 => (clone $reviews)->where('rating', 2)->count(),
                1 => (clone $reviews)->where('rating', 1)->count(),
            ]
        ]);
    }
}
