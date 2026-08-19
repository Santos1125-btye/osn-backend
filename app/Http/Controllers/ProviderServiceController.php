<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProviderServiceController extends Controller
{
    public function index(Request $request)
    {
        $provider = $request->user()->provider;

        $services = $provider->services()
            ->with('category')
            ->orderBy('display_order')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'category_id' => 'required|exists:categories,id',

            'sub_category' => 'required|string|max:255',

            'service_name' => 'required|string|max:255',

            'description' => 'nullable|string',

            'cover_image' => 'nullable|image|max:4096',

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'pricing_method' => 'required|in:fixed,range,consultation',

            'price' => 'required_if:pricing_method,fixed|nullable|numeric|min:0',

            'min_price' => 'required_if:pricing_method,range|nullable|numeric|min:0',

            'max_price' => 'required_if:pricing_method,range|nullable|numeric|gte:min_price',

            /*
            |--------------------------------------------------------------------------
            | Duration
            |--------------------------------------------------------------------------
            */

            'duration_method' => 'required|in:fixed,range,consultation',

            'duration' => 'required_if:duration_method,fixed|nullable|string|max:100',

            'min_duration' => 'required_if:duration_method,range|nullable|string|max:100',

            'max_duration' => 'required_if:duration_method,range|nullable|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Consultation
            |--------------------------------------------------------------------------
            */

            'consultation_type' =>
                'required_if:pricing_method,consultation|required_if:duration_method,consultation|nullable|in:phone,video,physical',

            'is_active' => 'nullable|boolean',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);

        }

        $provider = $request->user()->provider;

        $imagePath = null;

        if ($request->hasFile('cover_image')) {

            $imagePath = $request
                ->file('cover_image')
                ->store('providers/services', 'public');

        }

        $service = ProviderService::create([

            'provider_id' => $provider->id,

            'category_id' => $request->category_id,

            'sub_category' => $request->sub_category,

            'service_name' => $request->service_name,

            'description' => $request->description,

            'cover_image' => $imagePath,

            'pricing_method' => $request->pricing_method,

            'price' => $request->price,

            'min_price' => $request->min_price,

            'max_price' => $request->max_price,

            'duration_method' => $request->duration_method,

            'duration' => $request->duration,

            'min_duration' => $request->min_duration,

            'max_duration' => $request->max_duration,

            'consultation_type' => $request->consultation_type,

            'display_order' => 0,

            'is_active' => $request->boolean('is_active', true),

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service added successfully.',
            'service' => $service->load('category'),
        ]);
    }

    public function update(Request $request, ProviderService $providerService)
    {
        $provider = $request->user()->provider;

        if ($providerService->provider_id != $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [

            'category_id' => 'required|exists:categories,id',

            'sub_category' => 'required|string|max:255',

            'service_name' => 'required|string|max:255',

            'description' => 'nullable|string',

            'cover_image' => 'nullable|image|max:4096',

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'pricing_method' => 'required|in:fixed,range,consultation',

            'price' => 'required_if:pricing_method,fixed|nullable|numeric|min:0',

            'min_price' => 'required_if:pricing_method,range|nullable|numeric|min:0',

            'max_price' => 'required_if:pricing_method,range|nullable|numeric|gte:min_price',

            /*
            |--------------------------------------------------------------------------
            | Duration
            |--------------------------------------------------------------------------
            */

            'duration_method' => 'required|in:fixed,range,consultation',

            'duration' => 'required_if:duration_method,fixed|nullable|string|max:100',

            'min_duration' => 'required_if:duration_method,range|nullable|string|max:100',

            'max_duration' => 'required_if:duration_method,range|nullable|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Consultation
            |--------------------------------------------------------------------------
            */

            'consultation_type' =>
                'required_if:pricing_method,consultation|required_if:duration_method,consultation|nullable|in:phone,video,physical',

            'is_active' => 'nullable|boolean',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->hasFile('cover_image')) {

            if (
                $providerService->cover_image &&
                Storage::disk('public')->exists($providerService->cover_image)
            ) {
                Storage::disk('public')->delete($providerService->cover_image);
            }

            $providerService->cover_image = $request
                ->file('cover_image')
                ->store('providers/services', 'public');
        }

        $providerService->update([
            'category_id' => $request->category_id,
            'sub_category' => $request->sub_category,
            'service_name' => $request->service_name,
            'description' => $request->description,

            'pricing_method' => $request->pricing_method,
            'price' => $request->price,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,

            'duration_method' => $request->duration_method,
            'duration' => $request->duration,
            'min_duration' => $request->min_duration,
            'max_duration' => $request->max_duration,

            'consultation_type' => $request->consultation_type,

            'is_active' => $request->boolean('is_active', true),
        ]);

        $providerService->save();

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'service' => $providerService->fresh()->load('category'),
        ]);
    }

    public function destroy(Request $request, ProviderService $providerService)
    {
        $provider = $request->user()->provider;

        if ($providerService->provider_id != $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if (
            $providerService->cover_image &&
            Storage::disk('public')->exists($providerService->cover_image)
        ) {
            Storage::disk('public')->delete($providerService->cover_image);
        }

        $providerService->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ]);
    }

    public function toggleStatus(Request $request, ProviderService $providerService)
    {
        $provider = $request->user()->provider;

        if ($providerService->provider_id != $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $providerService->update([
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service status updated successfully.',
            'is_active' => $providerService->is_active,
        ]);
    }

    
}