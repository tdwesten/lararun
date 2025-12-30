<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $daily_recommendation_id
 * @property string $status
 * @property int|null $difficulty_rating
 * @property int|null $enjoyment_rating
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class WorkoutFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFeedbackFactory> */
    use HasFactory;

    protected $table = 'workout_feedback';

    protected $fillable = [
        'user_id',
        'daily_recommendation_id',
        'status',
        'difficulty_rating',
        'enjoyment_rating',
        'notes',
    ];

    /**
     * Get the user that owns the feedback.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the daily recommendation that owns the feedback.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DailyRecommendation, $this>
     */
    public function dailyRecommendation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DailyRecommendation::class);
    }
}
