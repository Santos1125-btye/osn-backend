<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Services\ConversationService;
use App\Models\BookingTimeline;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;

class ProviderBookingController extends Controller
{
    public function index(Request $request)
    {
        $provider = $request->user()->provider;

        $status = $request->status;

        $query = Booking::with([
            'customer',
            'service',
            'payment',
            'address',
            'timelines',
        ])->where('provider_id', $provider->id);

        $status = $request->status;
        $paymentStatus = $request->payment_status;

        if ($status) {

            if ($status === 'completed') {
                $query->whereIn('status', [
                    'provider_completed',
                    'completed',
                ]);
            } elseif ($status === 'cancelled') {
                $query->where('status', 'rejected');
            } else {
                $query->where('status', $status);
            }
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        return response()->json([
            'success' => true,
            'provider_id' => $provider->id,
            'user_id' => $request->user()->id,
            'bookings' => $query->latest()->get(),
        ]);
    }

    private function addTimeline(
        Booking $booking,
        string $status,
        string $title,
        string $description
    ) {
        BookingTimeline::create([

            'booking_id' => $booking->id,

            'status' => $status,

            'title' => $title,

            'description' => $description,

            'created_by' => 'provider',

        ]);
    }

    public function show(Request $request, Booking $booking)
    {
        if ($booking->provider_id !== $request->user()->provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'booking' => $booking->load([
                'customer',
                'service',
                'payment',
                'conversation',
                'address',
                'timelines',
            ]),
        ]);
    }

    public function setPrice(Request $request, Booking $booking)
    {
        $provider = $request->user()->provider;

        // Provider ownership
        if ($booking->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Must still be pending
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can have their price updated.',
            ], 422);
        }

        // Must still be unpaid
        if ($booking->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This booking has already been paid.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $amount = (float) $request->amount;

        $service = $booking->service;

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Booking service could not be found.',
            ], 404);
        }

        // Only range services can use provider pricing.
        if ($service->pricing_method !== 'range') {
            return response()->json([
                'success' => false,
                'message' => 'Only range-priced services can have their price set by the provider.',
            ], 422);
        }

        // Minimum price
        if (
            $service->min_price !== null &&
            $amount < (float) $service->min_price
        ) {
            return response()->json([
                'success' => false,
                'message' => 'The price cannot be below the minimum service price.',
            ], 422);
        }

        // Maximum price
        if (
            $service->max_price !== null &&
            $amount > (float) $service->max_price
        ) {
            return response()->json([
                'success' => false,
                'message' => 'The price cannot exceed the maximum service price.',
            ], 422);
        }

        $discount = (float) ($booking->discount_amount ?? 0);
        $homeServiceFee = (float) ($booking->home_service_fee ?? 0);

        $total = $amount - $discount + $homeServiceFee;

        $booking->update([
            'amount' => $amount,
            'total_amount' => $total,
        ]);

        $this->addTimeline(
            $booking,
            'price_set',
            'Price Set',
            'Provider set the booking price to ₦' .
            number_format($amount, 2)
        );

        NotificationService::send(
            $booking->customer_id,
            'Booking Price Set',
            'The provider has set the price for your booking. You can now proceed with payment.',
            Notification::TYPE_BOOKING,
            [
                'booking_id' => $booking->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking price updated successfully.',
            'booking' => $booking->fresh()->load([
                'customer',
                'provider.user',
                'service',
                'payment',
                'address',
                'timelines',
            ]),
        ]);
    }

    public function accept(Request $request, Booking $booking)
    {
        return $this->updateStatus(
            $request,
            $booking,
            'accepted'
        );
    }

    public function reject(Request $request, Booking $booking)
    {
        return $this->updateStatus($request, $booking, 'rejected');
    }

    public function start(Request $request, Booking $booking)
    {
        return $this->updateStatus($request, $booking, 'in_progress');
    }

    public function complete(Request $request, Booking $booking)
    {
        return $this->updateStatus(
            $request,
            $booking,
            'provider_completed'
        );
    }

    private function updateStatus(Request $request, Booking $booking, string $status)
    {
        if ($booking->provider_id !== $request->user()->provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $allowedTransitions = [
            'pending' => [
                'accepted',
                'rejected',
            ],

            'accepted' => [
                'in_progress',
            ],

            'in_progress' => [
                'provider_completed',
            ],

            'provider_completed' => [
                'completed',
            ],
        ];

        $current = $booking->status;

        if (
            !isset($allowedTransitions[$current]) ||
            !in_array($status, $allowedTransitions[$current])
        ) {
            return response()->json([
                'success' => false,
                'message' => "Cannot change booking from '{$current}' to '{$status}'."
            ], 422);
        }

        // Update booking status
        $data = [
            'status' => $status,
        ];

        switch ($status) {

            case 'accepted':
                $data['accepted_at'] = now();
                break;

            case 'rejected':
                $data['rejected_at'] = now();
                break;

            case 'in_progress':
                $data['started_at'] = now();
                break;

            case 'provider_completed':
                $data['provider_completed_at'] = now();
                break;

        }

        $booking->update($data);

        switch ($status) {

            case 'accepted':

                $this->addTimeline(
                    $booking,
                    'accepted',
                    'Booking Accepted',
                    'Provider accepted the booking.'
                );

                break;

            case 'rejected':

                $this->addTimeline(
                    $booking,
                    'rejected',
                    'Booking Rejected',
                    'Provider rejected the booking.'
                );

                break;

            case 'in_progress':

                $this->addTimeline(
                    $booking,
                    'in_progress',
                    'Job Started',
                    'Provider started the service.'
                );

                break;

            case 'provider_completed':

                $this->addTimeline(
                    $booking,
                    'provider_completed',
                    'Job Completed',
                    'Provider marked the service as completed.'
                );

                break;
        }

        switch ($status) {

            case 'accepted':

                NotificationService::send(

                    $booking->customer_id,

                    'Booking Accepted',

                    'Your booking has been accepted.',

                    Notification::TYPE_BOOKING,

                    [
                        'booking_id' => $booking->id,
                    ]

                );

                break;

            case 'rejected':

                NotificationService::send(

                    $booking->customer_id,

                    'Booking Rejected',

                    'Unfortunately your booking was rejected.',

                    Notification::TYPE_BOOKING,

                    [
                        'booking_id' => $booking->id,
                    ]

                );

                break;

            case 'in_progress':

                NotificationService::send(

                    $booking->customer_id,

                    'Service Started',

                    'Your provider has started the service.',

                    Notification::TYPE_BOOKING,

                    [
                        'booking_id' => $booking->id,
                    ]

                );

                break;

            case 'provider_completed':

                NotificationService::send(

                    $booking->customer_id,

                    'Service Completed',

                    'Please confirm completion of your booking.',

                    Notification::TYPE_BOOKING,

                    [
                        'booking_id' => $booking->id,
                    ]

                );

                break;
        }

        // Reload relationships
        $booking->load([
            'provider',
            'payment',
            'conversation'
        ]);

        /**
         * Booking Accepted
         */
        if ($status === 'accepted') {

            // Create conversation
            ConversationService::create($booking);

            // Move earnings to pending
            if (
                $booking->payment &&
                $booking->payment_status === 'paid'
            ) {
                WalletService::addPending(
                    $booking->provider,
                    $booking->payment->provider_amount
                );
            }
        }

        /**
         * Booking Completed
         */
        if ($status === 'completed') {

            // Close conversation
            if ($booking->conversation) {
                ConversationService::close(
                    $booking->conversation,
                    'completed'
                );
            }
        }

        /**
         * Booking Rejected
         */
        if (
            $status === 'rejected' &&
            $booking->conversation
        ) {
            ConversationService::close(
                $booking->conversation,
                'cancelled'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully.',
            'booking' => $booking->fresh()->load([
                'customer',
                'service',
                'payment',
                'conversation',
            ]),
        ]);
    }
}