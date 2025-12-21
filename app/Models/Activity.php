<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        ];
    }

    /**
     * Get the user that owns the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\Activity>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
