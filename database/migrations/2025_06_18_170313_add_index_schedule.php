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
            // Adding indexes to improve performance for schedule summary queries
            $table->index(['driver_id', 'unit_id', 'route_id'], 'idx_schedule_driver_unit_route');
            $table->index(['schedule_date'], 'idx_schedule_date');
            $table->index(['status'], 'idx_schedule_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedule_driver_unit_route');
            $table->dropIndex('idx_schedule_date');
            $table->dropIndex('idx_schedule_status');
        });
    }
};
