<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $comments = [
            5 => [
                'Excellent service. I was very impressed with the quality of the work.',
                'Amazing experience from start to finish. I will definitely book again.',
                'Very professional and friendly. The service was exactly what I expected.',
            ],

            4 => [
                'Great service and very professional. I was happy with the result.',
                'Really good experience. The provider did a great job.',
                'Good service overall. I would definitely recommend this provider.',
            ],

            3 => [
                'The service was okay and the provider was friendly.',
                'Average experience. The service was completed as expected.',
            ],

            2 => [
                'The service could have been better. There is room for improvement.',
            ],

            1 => [
                'Unfortunately, I was not satisfied with the service.',
            ],
        ];

        $bookings = Booking::where('status', 'completed')
            ->where('payment_status', 'paid')
            ->whereDoesntHave('review')
            ->inRandomOrder()
            ->take(10)
            ->get();

        foreach ($bookings as $booking) {
            $rating = fake()->numberBetween(3, 5);

            Review::create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'provider_id' => $booking->provider_id,
                'service_id' => $booking->service_id,
                'rating' => $rating,
                'comment' => fake()->randomElement(
                    $comments[$rating]
                ),
            ]);
        }

        $this->command->info(
            "{$bookings->count()} reviews seeded successfully."
        );
    }
}