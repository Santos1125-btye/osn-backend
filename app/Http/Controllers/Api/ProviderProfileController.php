<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Provider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\ProviderSocialLink;
use Illuminate\Support\Facades\DB;

class ProviderProfileController extends Controller
{
    public function show(Request $request)
    {
        $provider = Provider::with([
            'country',
            'state',
            'city',
            'socialLinks',
        ])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider profile not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'provider' => $provider,
        ]);
    }

    public function storeBusiness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|in:individual,registered_business',
            'phone' => 'required|string|max:20',
            'business_email' => 'required|email',
            'years_of_experience' => 'required|integer|min:0',
            'business_description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = Provider::updateOrCreate(
            [
                'user_id' => $request->user()->id,
            ],
            [
                'business_name' => $request->business_name,
                'business_type' => $request->business_type,
                'phone' => $request->phone,
                'business_email' => $request->business_email,
                'years_of_experience' => $request->years_of_experience,
                'business_description' => $request->business_description,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Business information saved.',
            'provider' => $provider,
        ]);
    }

    public function updateBranding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|max:5120',
            'cover_image' => 'nullable|image|max:5120',

            'remove_profile_image' => 'nullable|boolean',
            'remove_cover_image' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = Provider::where('user_id', $request->user()->id)->firstOrFail();

        if ($request->boolean('remove_profile_image')) {
            if ($provider->profile_image) {
                Storage::disk('public')->delete($provider->profile_image);
            }

            $provider->profile_image = null;
        } elseif ($request->hasFile('profile_image')) {
            if ($provider->profile_image) {
                Storage::disk('public')->delete($provider->profile_image);
            }

            $provider->profile_image = $request
                ->file('profile_image')
                ->store('providers/logo', 'public');
        }

        if ($request->boolean('remove_cover_image')) {
            if ($provider->cover_image) {
                Storage::disk('public')->delete($provider->cover_image);
            }

            $provider->cover_image = null;
        } elseif ($request->hasFile('cover_image')) {
            if ($provider->cover_image) {
                Storage::disk('public')->delete($provider->cover_image);
            }

            $provider->cover_image = $request
                ->file('cover_image')
                ->store('providers/cover', 'public');
        }

        $provider->save();

        return response()->json([
            'success' => true,
            'message' => 'Branding updated successfully.',
            'provider' => $provider,
        ]);
    }

    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'business_address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = Provider::where('user_id', $request->user()->id)->firstOrFail();

        $provider->update([
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'business_address' => $request->business_address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business location updated successfully.',
            'provider' => $provider,
        ]);
    }

    public function updateVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate_file' => 'required|file|mimes:jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = Provider::where('user_id', $request->user()->id)->firstOrFail();

        if ($request->hasFile('certificate_file')) {

            if ($provider->certificate_file) {
                Storage::disk('public')->delete($provider->certificate_file);
            }

            $provider->certificate_file = $request
                ->file('certificate_file')
                ->store('providers/certificates', 'public');

            // Reset status whenever a new certificate is uploaded
            $provider->verification_status = 'pending';
            $provider->rejection_reason = null;

            $provider->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificate uploaded successfully.',
            'provider' => $provider,
        ]);
    }

    public function updateSocialLinks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'links' => 'nullable|array',
            'links.*.platform' => 'required_with:links|string|max:50',
            'links.*.url' => 'required_with:links|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = Provider::where('user_id', $request->user()->id)->firstOrFail();

        DB::transaction(function () use ($provider, $request) {

            $provider->socialLinks()->delete();

            if ($request->filled('links')) {
                foreach ($request->links as $link) {
                    $provider->socialLinks()->create([
                        'platform' => $link['platform'],
                        'url' => $link['url'],
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Social links updated successfully.',
            'links' => $provider->socialLinks()->get(),
        ]);
    }

    public function submitOnboarding(Request $request)
    {
        $provider = Provider::with([
            'country',
            'state',
            'city',
            'socialLinks',
        ])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $missing = [];

        // Business Information
        if (empty($provider->business_name))
            $missing[] = 'Business Name';

        if (empty($provider->business_type))
            $missing[] = 'Business Type';

        if (empty($provider->phone))
            $missing[] = 'Phone Number';

        if (empty($provider->business_email))
            $missing[] = 'Business Email';

        if (empty($provider->years_of_experience))
            $missing[] = 'Years of Experience';

        // Branding
        if (empty($provider->profile_image))
            $missing[] = 'Business Logo';

        if (empty($provider->cover_image))
            $missing[] = 'Cover Banner';

        // Location
        if (empty($provider->country_id))
            $missing[] = 'Country';

        if (empty($provider->state_id))
            $missing[] = 'State';

        if (empty($provider->city_id))
            $missing[] = 'City';

        if (empty($provider->business_address))
            $missing[] = 'Business Address';

        // Verification
        if (empty($provider->certificate_file))
            $missing[] = 'Business Registration Certificate';

        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your onboarding.',
                'missing' => $missing,
            ], 422);
        }

        $provider->update([
            'onboarding_completed' => true,
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
        ]);
    }
    public function submitForVerification(Request $request)
    {
        $provider = Provider::where('user_id', $request->user()->id)->first();

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider profile not found.',
            ], 404);
        }

        $missing = [];

        if (empty($provider->business_name)) {
            $missing[] = 'Business Information';
        }

        if (empty($provider->profile_image)) {
            $missing[] = 'Business Logo';
        }

        if (empty($provider->cover_image)) {
            $missing[] = 'Cover Banner';
        }

        if (empty($provider->business_address)) {
            $missing[] = 'Business Location';
        }

        if (empty($provider->certificate_file)) {
            $missing[] = 'Business Certificate';
        }

        if ($provider->socialLinks()->count() == 0) {
            $missing[] = 'Online Presence';
        }

        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete all onboarding steps.',
                'missing' => $missing,
            ], 422);
        }

        $provider->update([
            'onboarding_status' => 'submitted',
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile submitted successfully. Your account is now under review.',
        ]);
    }
}