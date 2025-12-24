<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyRecommendations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DailyRecommendation::class);
    }
}
