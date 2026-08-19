<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Provider;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\BookingTimeline;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Create only pending bookings
        $this->seedBookings('pending', 10);
    }

    private function seedBookings(
        string $status,
        int $count
    ): void {
        for ($i = 0; $i < $count; $i++) {

            /*
            |--------------------------------------------------------------------------
            | Provider
            |--------------------------------------------------------------------------
            */

            $provider = Provider::has('services')
                ->inRandomOrder()
                ->first();

            if (!$provider) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Provider Service
            |--------------------------------------------------------------------------
            */

            $service = $provider->services()
                ->inRandomOrder()
                ->first();

            if (!$service) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            $customer = User::where('role', 'customer')
                ->has('addresses')
                ->inRandomOrder()
                ->first();

            if (!$customer) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Customer Address
            |--------------------------------------------------------------------------
            */

            $address = $customer->addresses()
                ->inRandomOrder()
                ->first();

            if (!$address) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            $price = $service->price ?? 20000;

            /*
            |--------------------------------------------------------------------------
            | Booking
            |--------------------------------------------------------------------------
            */

            $booking = Booking::create([
                'customer_id' => $customer->id,

                'provider_id' => $provider->id,

                'service_id' => $service->id,

                'address_id' => $address->id,

                'service_delivery' => 'customer_location',

                'booking_date' => now()
                    ->addDays(rand(1, 15))
                    ->toDateString(),

                'booking_time' => now()
                    ->setTime(rand(8, 17), 0)
                    ->format('H:i:s'),

                'amount' => $price,

                'discount_amount' => 0,

                'home_service_fee' => 3000,

                'total_amount' => $price + 3000,

                'promo_code' => null,

                'estimated_duration' => $service->duration
                    ?? '2 Hours',

                'payment_status' => 'paid',

                'status' => 'pending',

                'notes' => fake()->sentence(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            Payment::create([
                'booking_id' => $booking->id,

                'customer_id' => $customer->id,

                'provider_id' => $provider->id,

                'amount' => $booking->total_amount,

                'provider_amount' => $booking->amount,

                'platform_fee' => 3000,

                'currency' => 'NGN',

                'gateway' => 'paystack',

                'payment_method' => 'card',

                'status' => 'successful',

                'paid_by' => 'customer',

                'paid_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Booking Timeline
            |--------------------------------------------------------------------------
            */

            BookingTimeline::create([
                'booking_id' => $booking->id,

                'status' => 'pending',

                'title' => 'Pending',

                'description' => 'Booking created and awaiting provider response.',

                'created_by' => 'system',
            ]);
        }
    }
}