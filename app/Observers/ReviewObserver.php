<?php

namespace App\Observers;

use App\Models\Provider;
use App\Models\Review;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        $this->updateProviderRating($review->provider_id);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        $this->updateProviderRating($review->provider_id);
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->updateProviderRating($review->provider_id);
    }

    private function updateProviderRating($providerId): void
    {
        $provider = Provider::find($providerId);

        if (!$provider) {
            return;
        }

        $average = Review::where('provider_id', $providerId)
            ->avg('rating');

        $provider->update([
            'rating' => round($average ?? 0, 1),
        ]);
    }
}