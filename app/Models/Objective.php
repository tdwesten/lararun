<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $target_date
 * @property string $status
 * @property string|null $description
 * @property string|null $enhancement_prompt
 * @property array<string>|null $running_days
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class Objective extends Model
{
    /** @use HasFactory<\Database\Factories\ObjectiveFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'target_date',
        'status',
        'description',
        'enhancement_prompt',
        'running_days',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'running_days' => 'array',
        ];
    }

    /**
     * Get the user that owns the objective.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the daily recommendations for the objective.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<DailyRecommendation, $this>
     */
    public function dailyRecommendations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DailyRecommendation::class);
    }
}
