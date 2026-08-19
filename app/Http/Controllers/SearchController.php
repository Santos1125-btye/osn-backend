<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\ProviderService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->query('q', ''));

        if ($query === '') {
            return response()->json([
                'success' => true,
                'query' => '',
                'results' => [],
            ]);
        }

        $results = collect();

        /*
        |--------------------------------------------------------------------------
        | SERVICES
        |--------------------------------------------------------------------------
        */

        $services = ProviderService::with([
                'provider',
                'category',
            ])
            ->where('is_active', true)
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->where(function ($q) use ($query) {
                $q->where('service_name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sub_category', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        foreach ($services as $service) {
            $results->push([
                'type' => 'service',
                'id' => $service->id,
                'title' => $service->service_name,
                'subtitle' => $service->provider?->business_name ?? 'Service',
                'image' => $service->cover_image,
                'category_id' => $service->category_id,
                'sub_category' => $service->sub_category,
                'provider_id' => $service->provider_id,
                'provider' => $service->provider,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORIES
        |--------------------------------------------------------------------------
        |
        | Categories are obtained from active provider services.
        | This avoids requiring a separate Category search query.
        |
        */

        $categoryResults = ProviderService::with('category')
            ->where('is_active', true)
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('category', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->get()
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->values();

        foreach ($categoryResults as $category) {
            $results->push([
                'type' => 'category',
                'id' => $category->id,
                'title' => $category->name,
                'subtitle' => 'Category',
                'image' => $category->image ?? null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUBCATEGORIES
        |--------------------------------------------------------------------------
        |
        | Your provider_services table stores sub_category directly.
        |
        */

        $subCategoryResults = ProviderService::with('category')
            ->where('is_active', true)
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->where('sub_category', 'like', "%{$query}%")
            ->get()
            ->unique(function ($service) {
                return $service->category_id . '|' . $service->sub_category;
            })
            ->values();

        foreach ($subCategoryResults as $service) {
            if (!$service->sub_category) {
                continue;
            }

            $results->push([
                'type' => 'subCategory',
                'id' => $service->category_id,
                'title' => $service->sub_category,
                'subtitle' => $service->category?->name ?? 'Subcategory',
                'image' => null,
                'category_id' => $service->category_id,
                'sub_category' => $service->sub_category,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PROVIDERS
        |--------------------------------------------------------------------------
        */

        $providers = Provider::with([
                'services' => function ($query) {
                    $query->where('is_active', true)
                        ->with('category');
                },
            ])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('business_name', 'like', "%{$query}%")
                    ->orWhere('bio', 'like', "%{$query}%")
                    ->orWhere(
                        'business_description',
                        'like',
                        "%{$query}%"
                    );
            })
            ->limit(20)
            ->get();

        foreach ($providers as $provider) {
            $results->push([
                'type' => 'provider',
                'id' => $provider->id,
                'title' => $provider->business_name,
                'subtitle' => $provider->business_description
                    ?? 'Professional provider',
                'image' => $provider->profile_image,
                'services' => $provider->services,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $results->values(),
        ]);
    }
}