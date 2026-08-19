<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\Request;

class ProviderDashboardController extends Controller
{
    public function index(Request $request)
    {
        $provider = Provider::where(
            'user_id',
            $request->user()->id
        )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | BOOKINGS
        |--------------------------------------------------------------------------
        */

        $bookings = $provider->bookings();

        // Pending bookings waiting for provider action
        $jobRequests = (clone $bookings)
            ->where('status', 'pending')
            ->count();

        // Accepted / in-progress bookings
        $activeBookings = (clone $bookings)
            ->whereIn('status', [
                'accepted',
                'in_progress',
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | UPCOMING BOOKING
        |--------------------------------------------------------------------------
        */

        $upcomingBooking = (clone $bookings)
            ->with([
                'customer:id,first_name,last_name,phone',
                'service:id,service_name,cover_image,price',
            ])
            ->whereIn('status', [
                'accepted',
                'in_progress',
            ])
            ->whereDate('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('booking_time')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | RECENT ACTIVITIES
        |--------------------------------------------------------------------------
        */

        $recentActivities = (clone $bookings)
            ->with([
                'customer:id,first_name,last_name',
                'service:id,service_name',
            ])
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | WALLET
        |--------------------------------------------------------------------------
        */

        $walletBalance = $provider->wallet?->available_balance ?? 0;

        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        $averageRating = (float) $provider->rating;

        /*
        |--------------------------------------------------------------------------
        | NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        $unreadNotifications = Notification::where(
            'user_id',
            $request->user()->id
        )
            ->where('is_read', false)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [

                'provider' => [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                    'profile_image' => $provider->profile_image,
                    'verification_status' => $provider->verification_status,
                    'is_available' => $provider->is_available,
                ],

                'statistics' => [
                    'job_requests' => $jobRequests,
                    'active_bookings' => $activeBookings,
                    'wallet_balance' => $walletBalance,
                    'average_rating' => $averageRating,
                ],

                'notifications' => [
                    'unread' => $unreadNotifications,
                ],

                'upcoming_booking' => $upcomingBooking,

                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'is_available' => 'required|boolean',
        ]);

        $provider = Provider::where(
            'user_id',
            $request->user()->id
        )->firstOrFail();

        $provider->update([
            'is_available' => $request->is_available,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully.',
            'is_available' => $provider->is_available,
        ]);
    }

    public function verifyProvider(Provider $provider)
    {
        $provider->update([
            'verification_status' => 'verified',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider verified successfully.',
            'provider' => $provider,
        ]);
    }
}