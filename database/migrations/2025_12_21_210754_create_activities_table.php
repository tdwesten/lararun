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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('strava_id')->unique();
            $table->string('name');
            $table->string('type');
            $table->float('distance'); // meters
            $table->integer('moving_time'); // seconds
            $table->integer('elapsed_time'); // seconds
            $table->dateTime('start_date');

            // Zone data storage (JSON for each zone type)
            $table->json('zone_data')->nullable();

            // Flattened zone times (individual columns for quick queries)
            $table->integer('z1_time')->default(0);
            $table->integer('z2_time')->default(0);
            $table->integer('z3_time')->default(0);
            $table->integer('z4_time')->default(0);
            $table->integer('z5_time')->default(0);

            // Calculated intensity score (weighted by zones)
            $table->decimal('intensity_score', 8, 2)->default(0);

            // Zone data availability flag
            $table->boolean('zone_data_available')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
