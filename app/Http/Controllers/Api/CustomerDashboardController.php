<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\ProviderService;
use App\Models\Review;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Account Overview Statistics
        |--------------------------------------------------------------------------
        */

        $stats = [
            'bookings' => Booking::where('customer_id', $user->id)->count(),

            'completed' => Booking::where('customer_id', $user->id)
                ->where('status', 'completed')
                ->count(),

            'reviews' => Review::where('customer_id', $user->id)->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = [
            'id' => $user->id,
            'name' => $user->full_name,
            'profile_image' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = Category::query()
            ->where('status', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'icon',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Popular Services
        |--------------------------------------------------------------------------
        |
        | Ranked by booking activity first, then service rating.
        | Only active services, active providers and active categories
        | are included.
        |
        */

        $popularServices = ProviderService::query()
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('status', true);
            })
            ->whereHas('provider', function ($query) {
                $query->where('status', true);
            })
            ->with([
                'category:id,name',
            ])
            ->withCount('bookings')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('bookings_count')
            ->orderByDesc('reviews_avg_rating')
            ->orderBy('display_order')
            ->limit(10)
            ->get()
            ->map(function ($service) {

                /*
                |--------------------------------------------------------------------------
                | Rating
                |--------------------------------------------------------------------------
                */

                $rating = $service->reviews_avg_rating !== null
                    ? (float) $service->reviews_avg_rating
                    : (float) ($service->provider?->rating ?? 0);

                /*
                |--------------------------------------------------------------------------
                | Starting Price
                |--------------------------------------------------------------------------
                */

                $startingPrice = match ($service->pricing_method) {
                    'fixed' => '₦' . number_format(
                        (float) $service->price,
                        0
                    ),

                    'range' => '₦' .
                        number_format((float) $service->min_price, 0) .
                        ' - ₦' .
                        number_format((float) $service->max_price, 0),

                    'consultation' => 'Consultation',

                    default => 'Contact provider',
                };

                return [
                    'id' => $service->id,
                    'service' => $service->service_name,
                    'category' => $service->category?->name ?? 'Service',
                    'image' => $service->cover_image
                        ? asset('storage/' . $service->cover_image)
                        : null,
                    'rating' => round($rating, 1),
                    'starting_price' => $startingPrice,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Recent Bookings
        |--------------------------------------------------------------------------
        */

        $recentBookings = Booking::query()
            ->where('customer_id', $user->id)
            ->with([
                'provider:id,business_name,profile_image',
                'service:id,service_name,cover_image',
            ])
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($booking) {

                return [
                    'id' => $booking->id,

                    'image' => $booking->service?->cover_image
                        ? asset('storage/' . $booking->service->cover_image)
                        : null,

                    'service' => $booking->service?->service_name
                        ?? 'Service',

                    'provider' => $booking->provider?->business_name
                        ?? 'Provider',

                    'date' => $booking->booking_date,

                    'time' => $booking->booking_time,

                    'status' => $booking->status,

                    'payment_status' => $booking->payment_status,

                    'amount' => '₦' . number_format(
                        (float) $booking->total_amount,
                        0
                    ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'user' => $customer,

                'stats' => $stats,

                'categories' => $categories,

                'popular_services' => $popularServices,

                'recent_bookings' => $recentBookings,
            ],
        ]);
    }
}