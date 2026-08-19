<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProviderWorkingHour;
use App\Models\ProviderUnavailableDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Provider;

class ProviderAvailabilityController extends Controller
{
    public function show(Request $request)
    {
        $provider = $request->user()->provider;

        return response()->json([
            'success' => true,
            'data' => [
                'working_hours' => $provider->workingHours()->orderBy('id')->get(),
                'unavailable_dates' => $provider->unavailableDates()
                    ->orderBy('date')
                    ->get(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'working_days' => 'required|array',
            'working_days.*.day' => 'required|string',
            'working_days.*.is_available' => 'required|boolean',
            'working_days.*.start_time' => 'nullable|date_format:H:i',
            'working_days.*.end_time' => 'nullable|date_format:H:i',

            'unavailable_dates' => 'nullable|array',
            'unavailable_dates.*.date' => 'required|date',
            'unavailable_dates.*.reason' => 'nullable|string|max:255',
        ]);

        $provider = $request->user()->provider;

        DB::transaction(function () use ($provider, $validated) {

            foreach ($validated['working_days'] as $day) {
                ProviderWorkingHour::updateOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'day' => $day['day'],
                    ],
                    [
                        'is_available' => $day['is_available'],
                        'start_time' => $day['start_time'] ?? null,
                        'end_time' => $day['end_time'] ?? null,
                    ]
                );
            }

            $provider->unavailableDates()->delete();

            foreach ($validated['unavailable_dates'] ?? [] as $date) {
                ProviderUnavailableDate::create([
                    'provider_id' => $provider->id,
                    'date' => $date['date'],
                    'reason' => $date['reason'] ?? null,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully.',
        ]);
    }

    public function customerAvailability(Provider $provider)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'working_hours' => $provider->workingHours()
                    ->orderBy('id')
                    ->get(),

                'unavailable_dates' => $provider->unavailableDates()
                    ->orderBy('date')
                    ->get(),
            ],
        ]);
    }
}