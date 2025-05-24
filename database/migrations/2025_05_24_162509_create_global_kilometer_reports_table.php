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
        Schema::create('global_kilometer_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('route_id')->constrained('routes');
            $table->date('report_date');
            $table->double('kilometers', 8, 2)->default(0);
            $table->tinyInteger('period')->comment('1 or 2 (1-15 or 16-end of month)');
            $table->integer('month');
            $table->integer('year');
            $table->integer('driver_count')->default(1)->comment('Number of drivers sharing the kilometers');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate entries
            $table->unique(['driver_id', 'unit_id', 'report_date', 'period'], 'global_km_reports_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_kilometer_reports');
    }
};
