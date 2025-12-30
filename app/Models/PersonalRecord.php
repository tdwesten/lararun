<?php

namespace App\Models;

use App\Enums\RecordType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $activity_id
 * @property RecordType $record_type
 * @property float $value
 * @property \Illuminate\Support\Carbon $achieved_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PersonalRecord extends Model
{
    /** @use HasFactory<\Database\Factories\PersonalRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'record_type',
        'value',
        'achieved_date',
    ];

    protected function casts(): array
    {
        return [
            'achieved_date' => 'date',
            'value' => 'decimal:2',
            'record_type' => RecordType::class,
        ];
    }

    /**
     * Get the user that owns the record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity that set this record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Activity, $this>
     */
    public function activity(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
