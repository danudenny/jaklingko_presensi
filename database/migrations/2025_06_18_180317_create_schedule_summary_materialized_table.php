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
        Schema::create('schedule_summary_materialized', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->string('driver_name');
            $table->string('driver_type')->nullable();
            $table->string('driver_rekening')->nullable();
            $table->unsignedBigInteger('route_id');
            $table->string('route_name');
            $table->unsignedBigInteger('unit_id');
            $table->string('unit_number');
            $table->date('schedule_date');
            $table->integer('total_days')->default(1);
            $table->timestamps();
            
            // Add indexes for fast filtering
            $table->index(['driver_id', 'schedule_date']);
            $table->index(['route_id', 'schedule_date']);
            $table->index(['unit_id', 'schedule_date']);
            $table->index(['driver_type', 'schedule_date']);
            $table->index('schedule_date');
            
            // Composite index for common filter combinations
            $table->index(['driver_id', 'route_id', 'unit_id', 'schedule_date'], 'driver_route_unit_date_idx');
        });
        
        // Create the materialized view as a regular table that we'll populate
        DB::statement("
            CREATE OR REPLACE VIEW schedule_summary_view AS
            SELECT 
                s.driver_id,
                d.name as driver_name,
                d.type as driver_type,
                d.rekening as driver_rekening,
                s.route_id,
                r.name as route_name,
                s.unit_id,
                u.unit_number,
                s.schedule_date,
                1 as total_days
            FROM schedules s
            JOIN drivers d ON s.driver_id = d.id
            JOIN routes r ON s.route_id = r.id
            JOIN units u ON s.unit_id = u.id
            WHERE s.status = 'scheduled'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_summary_materialized');
    }
};
