<?php

namespace App\Http\Controllers;

use App\Models\ProviderService;
use Illuminate\Http\Request;

class CustomerProviderController extends Controller
{
    public function index($service)
    {
        $providerService = ProviderService::query()
            ->where('id', $service)
            ->where('is_active', true)
            ->with([
                'category:id,name,icon',
                'provider',
            ])
            ->first();

        if (!$providerService) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        $providers = ProviderService::query()
            ->where('category_id', $providerService->category_id)
            ->where('sub_category', $providerService->sub_category)
            ->where('service_name', $providerService->service_name)
            ->where('is_active', true)
            ->whereHas('provider', function ($query) {
                $query
                    ->where('is_active', true)
                    ->where('is_available', true);
            })
            ->with([
                'provider' => function ($query) {
                    $query->withCount([
                        'reviews',
                        'bookings as completed_jobs_count' => function ($bookingQuery) {
                            $bookingQuery->where(
                                'status',
                                'completed'
                            );
                        },
                    ]);
                },
            ])
            ->get()
            ->map(function ($service) {
                $provider = $service->provider;

                return [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                    'profile_image' => $provider->profile_image
                        ? asset(
                            'storage/' .
                            $provider->profile_image
                        )
                        : null,
                    'rating' => $provider->rating,
                    'reviews_count' =>
                        $provider->reviews_count,
                    'completed_jobs' =>
                        $provider->completed_jobs_count,
                    'is_available' =>
                        $provider->is_available,
                    'business_type' =>
                        $provider->business_type,
                    'bio' => $provider->bio,
                    'years_of_experience' =>
                        $provider->years_of_experience,
                    'business_address' =>
                        $provider->business_address,
                    'latitude' =>
                        $provider->latitude,

                    'verification_status' =>
                        $provider->verification_status,

                    'longitude' =>
                        $provider->longitude,

                    'service' => [
                        'id' => $service->id,
                        'name' => $service->service_name,
                        'sub_category' =>
                            $service->sub_category,
                        'pricing_method' =>
                            $service->pricing_method,
                        'price' => $service->price,
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
                    ],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,

            'service' => [
                'id' => $providerService->id,
                'name' => $providerService->service_name,
                'category' => [
                    'id' =>
                        $providerService->category->id,
                    'name' =>
                        $providerService->category->name,
                    'icon' =>
                        $providerService->category->icon,
                ],
                'sub_category' =>
                    $providerService->sub_category,
            ],

            'providers' => $providers,
        ]);
    }
}