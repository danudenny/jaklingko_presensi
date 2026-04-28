<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing table and recreate with new structure
        Schema::dropIfExists('schedule_summary_materialized');
        
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
            $table->year('year'); // Year (e.g., 2025)
            $table->tinyInteger('month'); // Month (1-12)
            $table->integer('total_days'); // Total days worked in that month
            $table->timestamps();
            
            // Add indexes for fast filtering
            $table->index(['driver_id', 'year', 'month']);
            $table->index(['route_id', 'year', 'month']);
            $table->index(['unit_id', 'year', 'month']);
            $table->index(['driver_type', 'year', 'month']);
            $table->index(['year', 'month']);
            
            // Composite index for common filter combinations
            $table->index(['driver_id', 'route_id', 'unit_id', 'year', 'month'], 'driver_route_unit_ym_idx');
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['driver_id', 'route_id', 'unit_id', 'year', 'month'], 'unique_driver_route_unit_month');
        });
        
        // Update the view to reflect monthly aggregation
        DB::statement("DROP VIEW IF EXISTS schedule_summary_view");
        DB::statement("
            CREATE VIEW schedule_summary_view AS
            SELECT 
                s.driver_id,
                d.name as driver_name,
                d.type as driver_type,
                d.rekening as driver_rekening,
                s.route_id,
                r.name as route_name,
                s.unit_id,
                u.unit_number,
                EXTRACT(YEAR FROM s.schedule_date) as year,
                EXTRACT(MONTH FROM s.schedule_date) as month,
                COUNT(*) as total_days
            FROM schedules s
            JOIN drivers d ON s.driver_id = d.id
            JOIN routes r ON s.route_id = r.id
            JOIN units u ON s.unit_id = u.id
            WHERE s.status = 'scheduled'
            GROUP BY s.driver_id, d.name, d.type, d.rekening, s.route_id, r.name, s.unit_id, u.unit_number, EXTRACT(YEAR FROM s.schedule_date), EXTRACT(MONTH FROM s.schedule_date)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original table structure
        Schema::dropIfExists('schedule_summary_materialized');
        
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
        });
        
        // Restore the original view
        DB::statement("DROP VIEW IF EXISTS schedule_summary_view");
        DB::statement("
            CREATE VIEW schedule_summary_view AS
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
};
