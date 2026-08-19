<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ProviderService;
use Illuminate\Http\Request;

class CustomerServiceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category' => 'required|string|max:255',
        ]);

        $category = Category::query()
            ->where('id', $request->category_id)
            ->where('status', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $services = ProviderService::query()
            ->where('category_id', $category->id)
            ->where('sub_category', $request->sub_category)
            ->where('is_active', true)
            ->whereHas('provider', function ($query) {
                $query->where('status', true);
            })
            ->with([
                'category:id,name',
                'provider:id,user_id,business_name,profile_image,rating',
            ])
            ->orderBy('display_order')
            ->latest()
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_name' => $service->service_name,
                    'description' => $service->description,
                    'cover_image' => $service->cover_image
                        ? asset('storage/' . $service->cover_image)
                        : null,

                    'pricing_method' => $service->pricing_method,
                    'price' => $service->price,
                    'min_price' => $service->min_price,
                    'max_price' => $service->max_price,

                    'duration_method' => $service->duration_method,
                    'duration' => $service->duration,
                    'min_duration' => $service->min_duration,
                    'max_duration' => $service->max_duration,

                    'consultation_type' => $service->consultation_type,

                    'category' => [
                        'id' => $service->category->id,
                        'name' => $service->category->name,
                    ],

                    'provider' => [
                        'id' => $service->provider->id,
                        'user_id' => $service->provider->user_id,
                        'business_name' => $service->provider->business_name,
                        'profile_image' => $service->provider->profile_image
                            ? asset('storage/' . $service->provider->profile_image)
                            : null,
                        'rating' => $service->provider->rating,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
            ],
            'sub_category' => $request->sub_category,
            'services' => $services,
        ]);
    }
}