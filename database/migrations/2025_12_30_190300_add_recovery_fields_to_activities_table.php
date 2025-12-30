<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('recovery_score', 3, 1)->nullable()->after('extended_evaluation')->comment('0-10 scale, calculated from intensity and recent activities');
            $table->integer('estimated_recovery_hours')->nullable()->after('recovery_score')->comment('Estimated hours needed to recover');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['recovery_score', 'estimated_recovery_hours']);
        });
    }
};
