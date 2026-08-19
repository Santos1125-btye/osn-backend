<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Models\BookingTimeline;
use App\Services\WalletService;
use App\Services\ConversationService;
use App\Models\Conversation;
use App\Models\ProviderService;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['provider.user', 'service'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'provider_id' => 'required|exists:providers,id',

            'service_id' => 'required|exists:provider_services,id',

            'address_id' => 'nullable|exists:addresses,id',

            'service_delivery' => 'required|in:customer_location,provider_location',

            'booking_date' => 'required|date',

            'booking_time' => 'required',

            'notes' => 'nullable|string',

            'promo_code' => 'nullable|string',

            'estimated_duration' => 'nullable|string',

            'amount' => 'required|numeric',

            'discount_amount' => 'nullable|numeric',

            'home_service_fee' => 'nullable|numeric',

            'total_amount' => 'required|numeric',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);

        }

        if (
            $request->service_delivery === 'customer_location' &&
            !$request->address_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'A service address is required for home service.',
            ], 422);
        }

        $provider = Provider::findOrFail($request->provider_id);

        $providerService = ProviderService::where('id', $request->service_id)
            ->where('id', $request->service_id)
            ->first();

        if (!$providerService) {
            return response()->json([
                'success' => false,
                'message' => 'Selected provider does not offer this service.',
            ], 400);
        }

        $booking = Booking::create([

            'customer_id' => $request->user()->id,

            'provider_id' => $provider->id,

            'service_id' => $request->service_id,

            'address_id' => $request->address_id,

            'service_delivery' => $request->service_delivery,

            'booking_date' => $request->booking_date,

            'booking_time' => $request->booking_time,

            'amount' => $request->amount,

            'discount_amount' => $request->discount_amount ?? 0,

            'home_service_fee' => $request->home_service_fee ?? 0,

            'total_amount' => $request->total_amount,

            'promo_code' => $request->promo_code,

            'estimated_duration' => $request->estimated_duration,

            'payment_status' => 'pending',

            'status' => 'pending',

            'notes' => $request->notes,

        ]);

        BookingTimeline::create([

            'booking_id' => $booking->id,

            'status' => 'pending',

            'title' => 'Booking Created',

            'description' => 'Customer created booking.',

            'created_by' => 'customer',

        ]);

        NotificationService::send(

            $booking->customer_id,

            'Booking Submitted',

            'Your booking has been submitted successfully.',

            Notification::TYPE_BOOKING,

            [
                'booking_id' => $booking->id,
            ]

        );

        NotificationService::send(

            $booking->provider->user_id,

            'New Booking',

            'You have received a new booking request.',

            Notification::TYPE_BOOKING,

            [
                'booking_id' => $booking->id,
            ]

        );

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'booking' => $booking->load([
                'provider.user',
                'service',
                'review',
            ]),
        ], 201);
    }

    public function show(Booking $booking)
    {
        return response()->json([
            'success' => true,
            'booking' => $booking->load([
                'provider.user',
                'service',
                'dispute',
                'review',
            ]),
        ]);
    }

    public function confirm(Request $request, Booking $booking)
    {
        if ($booking->customer_id != $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'provider_completed') {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be confirmed yet.',
            ], 422);
        }

        if ($booking->dispute) {
            return response()->json([
                'success' => false,
                'message' => 'This booking has an active dispute and cannot be completed yet.',
            ], 422);
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Conversation::firstOrCreate(
            [
                'booking_id' => $booking->id,
            ],
            [
                'conversation_type' => 'booking',
                'customer_id' => $booking->customer_id,
                'provider_id' => $booking->provider_id,
                'status' => 'active',
            ]
        );

        BookingTimeline::create([
            'booking_id' => $booking->id,
            'status' => 'completed',
            'title' => 'Booking Completed',
            'description' => 'Customer confirmed completion.',
            'created_by' => 'customer',
        ]);

        if (
            $booking->payment &&
            $booking->payment_status === 'paid'
        ) {
            WalletService::releasePending(
                $booking->provider,
                $booking->payment->provider_amount
            );
        }

        if ($booking->conversation) {
            ConversationService::close(
                $booking->conversation,
                'completed'
            );
        }

        NotificationService::send(

            $booking->provider->user_id,

            'Booking Completed',

            'Customer confirmed the booking completion.',

            Notification::TYPE_BOOKING,

            [
                'booking_id' => $booking->id,
            ]

        );

        return response()->json([
            'success' => true,
            'message' => 'Booking completed successfully.',
            'booking' => $booking->fresh()->load([
                'customer',
                'provider.user',
                'service',
                'payment',
                'timelines',
                'dispute',
            ]),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if (
            $booking->status !== 'pending' ||
            $booking->payment_status !== 'pending'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending unpaid bookings can be cancelled.',
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        BookingTimeline::create([
            'booking_id' => $booking->id,
            'status' => 'cancelled',
            'title' => 'Booking Cancelled',
            'description' => 'Customer cancelled the booking.',
            'created_by' => 'customer',
        ]);

        NotificationService::send(
            $booking->provider->user_id,
            'Booking Cancelled',
            'The customer has cancelled this booking.',
            Notification::TYPE_BOOKING,
            [
                'booking_id' => $booking->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'booking' => $booking->fresh()->load([
                'customer',
                'provider.user',
                'service',
                'payment',
                'timelines',
            ]),
        ]);
    }
}