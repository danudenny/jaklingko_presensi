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
        Schema::table('driver_schedule_history', function (Blueprint $table) {
            // Rename schedule_count to total_schedules for consistency with the code
            $table->renameColumn('schedule_count', 'total_schedules');
            
            // Add morning_shifts and afternoon_shifts columns
            $table->integer('morning_shifts')->default(0);
            $table->integer('afternoon_shifts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_schedule_history', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn('morning_shifts');
            $table->dropColumn('afternoon_shifts');
            
            // Restore the original column name
            $table->renameColumn('total_schedules', 'schedule_count');
        });
    }
};
