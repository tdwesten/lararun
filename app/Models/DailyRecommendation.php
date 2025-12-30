<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $objective_id
 * @property \Illuminate\Support\Carbon|null $date
 * @property string $type
 * @property string $title
 * @property string $description
 * @property string $reasoning
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DailyRecommendation extends Model
{
    /** @use HasFactory<\Database\Factories\DailyRecommendationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'objective_id',
        'date',
        'type',
        'title',
        'description',
        'reasoning',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Get the user that owns the recommendation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the objective that owns the recommendation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Objective, $this>
     */
    public function objective(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Objective::class);
    }

    /**
     * Get the workout feedback for this recommendation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<WorkoutFeedback, $this>
     */
    public function feedback(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WorkoutFeedback::class);
    }
}
