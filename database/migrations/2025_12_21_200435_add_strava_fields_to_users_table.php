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
            $table->string('strava_id')->nullable()->unique()->after('id');
            $table->string('strava_token')->nullable()->after('password');
            $table->string('strava_refresh_token')->nullable()->after('strava_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['strava_id', 'strava_token', 'strava_refresh_token']);
        });
    }
};
