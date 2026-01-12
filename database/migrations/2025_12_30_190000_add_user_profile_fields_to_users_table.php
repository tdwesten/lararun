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
            $table->integer('age')->nullable()->after('locale');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('age');
            $table->enum('fitness_level', ['beginner', 'intermediate', 'advanced', 'elite'])->default('intermediate')->after('weight_kg');
            $table->text('injury_history')->nullable()->after('fitness_level');
            $table->text('training_preferences')->nullable()->after('injury_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age', 'weight_kg', 'fitness_level', 'injury_history', 'training_preferences']);
        });
    }
};
