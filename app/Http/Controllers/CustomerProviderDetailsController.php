<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerProviderDetailsController extends Controller
{
    public function show(Request $request, $providerId)
    {
        $provider = Provider::with([
            'user',
            'services.category',
        ])
            ->withCount([
                'reviews',
                'bookings as completed_jobs_count' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->where('id', $providerId)
            ->where('is_active', true)
            ->first();

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Selected Service
        |--------------------------------------------------------------------------
        */

        $service = null;

        if ($request->filled('service_id')) {
            $service = $provider->services
                ->where('id', (int) $request->service_id)
                ->where('is_active', true)
                ->first();
        }

        if (!$service) {
            $service = $provider->services
                ->where('is_active', true)
                ->first();
        }

        $serviceReviewsCount = 0;

        if ($service) {
            $serviceReviewsCount = $provider->reviews()
                ->where('service_id', $service->id)
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Reviews
        |--------------------------------------------------------------------------
        */

        $reviewsQuery = $provider->reviews()
            ->with('customer:id,first_name,last_name,profile_image')
            ->latest();

        if ($service) {
            $reviewsQuery->where(
                'service_id',
                $service->id
            );
        }

        $reviews = $reviewsQuery
            ->take(10)
            ->get()
            ->map(function ($review) {
                $customer = $review->customer;

                $customerName = trim(
                    ($customer?->first_name ?? '') .
                    ' ' .
                    ($customer?->last_name ?? '')
                );

                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,

                    'customer' => [
                        'id' => $customer?->id,
                        'name' => $customerName !== ''
                            ? $customerName
                            : 'Customer',
                        'profile_image' =>
                            $customer?->profile_image
                            ? asset(
                                'storage/' .
                                $customer->profile_image
                            )
                            : null,
                    ],
                ];
            })
            ->values();

        /*
|--------------------------------------------------------------------------
| Portfolio
|--------------------------------------------------------------------------
*/

        $portfolioQuery = $provider->portfolios()
            ->where('is_active', true)
            ->latest('completed_at');

        if ($service) {
            $portfolioQuery->where(
                'provider_service_id',
                $service->id
            );
        }

        $portfolioItems = $portfolioQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Portfolio Images
        |--------------------------------------------------------------------------
        */

        $portfolioIds = $portfolioItems
            ->pluck('id')
            ->values()
            ->all();

        $portfolioImages = collect();

        if (!empty($portfolioIds)) {
            $portfolioImages = DB::table('provider_portfolio_images')
                ->whereIn('portfolio_id', $portfolioIds)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->groupBy('portfolio_id');
        }

        /*
        |--------------------------------------------------------------------------
        | Format Portfolio
        |--------------------------------------------------------------------------
        */

        $portfolio = $portfolioItems
            ->map(function ($item) use ($portfolioImages) {
                $images = $portfolioImages
                    ->get($item->id, collect())
                    ->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image' => $image->image
                                ? asset(
                                    'storage/' .
                                    $image->image
                                )
                                : null,
                            'display_order' =>
                                $image->display_order,
                        ];
                    })
                    ->values();

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,

                    'cover_image' => $item->cover_image
                        ? asset(
                            'storage/' .
                            $item->cover_image
                        )
                        : null,

                    'completed_at' =>
                        $item->completed_at,

                    'images' => $images,
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

            'provider' => [
                'id' => $provider->id,
                'user_id' => $provider->user_id,

                'business_name' =>
                    $provider->business_name,

                'business_type' =>
                    $provider->business_type,

                'phone' =>
                    $provider->phone,

                'business_email' =>
                    $provider->business_email,

                'bio' =>
                    $provider->bio,

                'business_description' =>
                    $provider->business_description,

                'profile_image' =>
                    $provider->profile_image
                    ? asset(
                        'storage/' .
                        $provider->profile_image
                    )
                    : null,

                'cover_image' =>
                    $provider->cover_image
                    ? asset(
                        'storage/' .
                        $provider->cover_image
                    )
                    : null,

                'rating' =>
                    $provider->rating,

                'reviews_count' =>
                    $provider->reviews_count,

                'completed_jobs' =>
                    $provider->completed_jobs_count,

                'is_available' =>
                    $provider->is_available,

                'status' =>
                    $provider->status,

                'years_of_experience' =>
                    $provider->years_of_experience,

                'verification_status' =>
                    $provider->verification_status,

                'onboarding_completed' =>
                    $provider->onboarding_completed,

                'business_address' =>
                    $provider->business_address,

                'latitude' =>
                    $provider->latitude,

                'longitude' =>
                    $provider->longitude,

                'user' => $provider->user
                    ? [
                        'id' => $provider->user->id,
                        'first_name' =>
                            $provider->user->first_name,
                        'last_name' =>
                            $provider->user->last_name,
                        'full_name' =>
                            $provider->user->full_name,
                        'profile_image' =>
                            $provider->user->profile_image
                            ? asset(
                                'storage/' .
                                $provider->user->profile_image
                            )
                            : null,
                    ]
                    : null,
            ],

            'service' => $service
                ? [
                    'id' => $service->id,

                    'name' =>
                        $service->service_name,

                    'description' =>
                        $service->description,

                    'sub_category' =>
                        $service->sub_category,

                    'cover_image' =>
                        $service->cover_image
                        ? asset(
                            'storage/' .
                            $service->cover_image
                        )
                        : null,

                    'pricing_method' =>
                        $service->pricing_method,

                    'price' =>
                        $service->price,

                    'min_price' =>
                        $service->min_price,

                    'max_price' =>
                        $service->max_price,

                    'duration_method' =>
                        $service->duration_method,

                    'duration' =>
                        $service->duration,

                    'min_duration' =>
                        $service->min_duration,

                    'max_duration' =>
                        $service->max_duration,

                    'consultation_type' =>
                        $service->consultation_type,

                    'category' => $service->category
                        ? [
                            'id' =>
                                $service->category->id,
                            'name' =>
                                $service->category->name,
                            'icon' =>
                                $service->category->icon,
                        ]
                        : null,
                ]
                : null,

            'reviews' => $reviews,

            'service_reviews_count' => $serviceReviewsCount,

            'portfolio' => $portfolio,
        ]);
    }
}