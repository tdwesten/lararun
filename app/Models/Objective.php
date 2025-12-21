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
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
