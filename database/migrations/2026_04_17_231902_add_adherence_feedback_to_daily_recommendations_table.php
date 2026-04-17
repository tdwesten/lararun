<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_recommendations', function (Blueprint $table) {
            $table->text('adherence_feedback')->nullable()->after('reasoning');
        });
    }

    public function down(): void
    {
        Schema::table('daily_recommendations', function (Blueprint $table) {
            $table->dropColumn('adherence_feedback');
        });
    }
};
