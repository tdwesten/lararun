<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $strava_id
 * @property string $name
 * @property string $type
 * @property float $distance
 * @property int $moving_time
 * @property int $elapsed_time
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property array<string, mixed>|null $zone_data
 * @property int|null $z1_time
 * @property int|null $z2_time
 * @property int|null $z3_time
 * @property int|null $z4_time
 * @property int|null $z5_time
 * @property float|null $intensity_score
 * @property bool $zone_data_available
 * @property string|null $short_evaluation
 * @property string|null $extended_evaluation
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read float|null $best_pace_seconds
 */
class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'strava_id',
        'name',
        'type',
        'distance',
        'moving_time',
        'elapsed_time',
        'start_date',
        'zone_data',
        'z1_time',
        'z2_time',
        'z3_time',
        'z4_time',
        'z5_time',
        'intensity_score',
        'zone_data_available',
        'short_evaluation',
        'extended_evaluation',
        'recovery_score',
        'estimated_recovery_hours',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'zone_data' => 'array',
            'zone_data_available' => 'boolean',
            'intensity_score' => 'decimal:2',
            'recovery_score' => 'decimal:1',
        ];
    }

    /**
     * Get the user that owns the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
