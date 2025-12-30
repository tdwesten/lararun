<?php

use App\Enums\RecordType;
use App\Models\Activity;
use App\Models\User;
use App\Services\PersonalRecordService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $service = app(PersonalRecordService::class);
        
        // Get all users with running activities
        User::whereHas('activities', function ($query) {
            $query->where('type', 'Run')->where('distance', '>', 0);
        })->chunk(100, function ($users) use ($service) {
            foreach ($users as $user) {
                // Get all running activities for this user
                Activity::where('user_id', $user->id)
                    ->where('type', 'Run')
                    ->where('distance', '>', 0)
                    ->orderBy('start_date')
                    ->chunk(100, function ($activities) use ($service) {
                        foreach ($activities as $activity) {
                            // Check and update records for each activity
                            $service->checkAndUpdateRecords($activity);
                        }
                    });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Personal records will be handled by the main table migration rollback
    }
};
