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
        Schema::table('users', function (Blueprint $table) {
            $table->string('experience_level')->nullable()->after('email');
            $table->json('training_days')->nullable()->after('experience_level');
            $table->boolean('notifications_enabled')->default(true)->after('training_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['experience_level', 'training_days', 'notifications_enabled']);
        });
    }
};
