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
        Schema::create('workout_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_recommendation_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['completed', 'skipped', 'partially_completed'])->default('completed');
            $table->integer('difficulty_rating')->nullable()->comment('1-5 scale, 1=too easy, 5=too hard');
            $table->integer('enjoyment_rating')->nullable()->comment('1-5 scale');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'daily_recommendation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_feedback');
    }
};
