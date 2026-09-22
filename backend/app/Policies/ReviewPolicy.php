<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only the author may edit their own review. Moderation is admin-only
     * and handled through the admin management endpoints.
     */
    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    /**
     * Only the author may delete their own review.
     */
    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
