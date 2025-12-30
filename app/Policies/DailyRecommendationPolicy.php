<?php

namespace App\Policies;

use App\Models\DailyRecommendation;
use App\Models\User;

class DailyRecommendationPolicy
{
    /**
     * Determine if the given recommendation can be viewed by the user.
     */
    public function view(User $user, DailyRecommendation $recommendation): bool
    {
        return $user->id === $recommendation->user_id;
    }

    /**
     * Determine if the given recommendation can be updated by the user.
     */
    public function update(User $user, DailyRecommendation $recommendation): bool
    {
        return $user->id === $recommendation->user_id;
    }

    /**
     * Determine if the given recommendation can be deleted by the user.
     */
    public function delete(User $user, DailyRecommendation $recommendation): bool
    {
        return $user->id === $recommendation->user_id;
    }
}
