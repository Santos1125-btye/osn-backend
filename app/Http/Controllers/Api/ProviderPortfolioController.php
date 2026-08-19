<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderPortfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProviderPortfolioController extends Controller
{
    public function index(Request $request)
    {
        $provider = $request->user()->provider;

        $portfolios = $provider->portfolios()
            ->with([
                'service.category',
                'images',
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'portfolios' => $portfolios,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'provider_service_id' => 'required|exists:provider_services,id',

            'title' => 'required|string|max:255',

            'description' => 'required|string',

            'cover_image' => 'required|image|max:4096',

            'images.*' => 'nullable|image|max:4096',

            'completed_at' => 'nullable|date',

            'is_active' => 'nullable|boolean',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = $request->user()->provider;
        if (
            !$provider->services()
                ->where('id', $request->provider_service_id)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service selected.',
            ], 403);
        }

        $coverImage = $request->file('cover_image')->store(
            'providers/portfolio',
            'public'
        );

        $portfolio = ProviderPortfolio::create([

            'provider_id' => $provider->id,

            'provider_service_id' => $request->provider_service_id,

            'title' => $request->title,

            'description' => $request->description,

            'cover_image' => $coverImage,

            'completed_at' => $request->completed_at,

            'is_active' => $request->boolean('is_active', true),

        ]);

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $index => $image) {

                $path = $image->store(
                    'providers/portfolio',
                    'public'
                );

                $portfolio->images()->create([

                    'image' => $path,

                    'display_order' => $index,

                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Portfolio created successfully.',
        ]);
    }

    public function show(Request $request, ProviderPortfolio $portfolio)
    {
        if ($portfolio->provider_id !== $request->user()->provider->id) {

            return response()->json([

                'success' => false,

                'message' => 'Unauthorized.'

            ], 403);
        }

        return response()->json([
            'success' => true,
            'portfolio' => $portfolio->load([
                'service.category',
                'images',
            ]),
        ]);
    }

    public function update(Request $request, ProviderPortfolio $portfolio)
    {
        if ($portfolio->provider_id !== $request->user()->provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $provider = $request->user()->provider;

        if (
            !$provider->services()
                ->where('id', $request->provider_service_id)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service selected.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [

            'provider_service_id' => 'required|exists:provider_services,id',

            'title' => 'required|string|max:255',

            'description' => 'required|string',

            'cover_image' => 'nullable|image|max:4096',

            'images.*' => 'nullable|image|max:4096',

            'deleted_images' => 'nullable|array',

            'deleted_images.*' => 'integer|exists:provider_portfolio_images,id',

            'completed_at' => 'nullable|date',

            'is_active' => 'nullable|boolean',

        ]);

        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'errors' => $validator->errors(),

            ], 422);
        }

        if ($request->hasFile('cover_image')) {

            Storage::disk('public')->delete(
                $portfolio->cover_image
            );

            $portfolio->cover_image = $request
                ->file('cover_image')
                ->store(
                    'providers/portfolio',
                    'public'
                );
        }

        $portfolio->update([

            'provider_service_id' => $request->provider_service_id,

            'title' => $request->title,

            'description' => $request->description,

            'completed_at' => $request->completed_at,

            'is_active' => $request->boolean('is_active', true),

            'cover_image' => $portfolio->cover_image,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Delete Selected Images
        |--------------------------------------------------------------------------
        */

        if ($request->filled('deleted_images')) {

            foreach ($request->deleted_images as $id) {

                $image = $portfolio->images()
                    ->where('id', $id)
                    ->first();

                if ($image) {

                    Storage::disk('public')->delete(
                        $image->image
                    );

                    $image->delete();
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Add New Images
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('images')) {

            $count = $portfolio->images()->count();

            foreach ($request->file('images') as $index => $image) {

                $path = $image->store(
                    'providers/portfolio',
                    'public'
                );

                $portfolio->images()->create([

                    'image' => $path,

                    'display_order' => $count + $index,

                ]);
            }
        }

        return response()->json([

            'success' => true,

            'message' => 'Portfolio updated successfully.',

        ]);
    }

    public function destroy(Request $request, ProviderPortfolio $portfolio)
    {
        if ($portfolio->provider_id !== $request->user()->provider->id) {

            return response()->json([

                'success' => false,

                'message' => 'Unauthorized.'

            ], 403);
        }
        Storage::disk('public')->delete(
            $portfolio->cover_image
        );

        foreach ($portfolio->images as $image) {

            Storage::disk('public')->delete(
                $image->image
            );
        }

        $portfolio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Portfolio deleted successfully.',
        ]);
    }
}
