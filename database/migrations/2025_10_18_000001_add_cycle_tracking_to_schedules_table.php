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
        Schema::table('schedules', function (Blueprint $table) {
            // Add cycle tracking for batangan drivers
            // cycle_day tracks position in the 7-day cycle (1-7)
            // Day 1-6: Working days
            // Day 7: Off/Cadangan day
            $table->tinyInteger('cycle_day')->nullable()->after('shift')
                ->comment('Position in 7-day cycle for batangan drivers (1-6=work, 7=off/cadangan)');
            
            // Add status field to mark schedule type
            $table->enum('schedule_type', ['regular', 'off', 'cadangan_cover'])->default('regular')->after('cycle_day')
                ->comment('regular=normal shift, off=rest day, cadangan_cover=covered by cadangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['cycle_day', 'schedule_type']);
        });
    }
};
