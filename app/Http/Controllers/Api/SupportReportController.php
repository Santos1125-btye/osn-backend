<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportReport;
use Illuminate\Http\Request;

class SupportReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|min:5|max:5000',
        ]);

        $report = SupportReport::create([
            'user_id' => $request->user()->id,
            'description' => $validated['description'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your report has been submitted successfully.',
            'data' => $report,
        ], 201);
    }

    public function index(Request $request)
    {
        $reports = SupportReport::where(
            'user_id',
            $request->user()->id
        )
        ->latest()
        ->paginate(20);

        return response()->json($reports);
    }
}