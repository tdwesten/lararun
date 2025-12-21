<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function objective(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Objective::class);
    }
}
