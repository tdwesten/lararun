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
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->enum('record_type', ['fastest_5k', 'fastest_10k', 'fastest_half_marathon', 'fastest_marathon', 'longest_run', 'fastest_pace'])->index();
            $table->decimal('value', 10, 2)->comment('Time in seconds or distance in meters');
            $table->date('achieved_date');
            $table->timestamps();

            $table->unique(['user_id', 'record_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
