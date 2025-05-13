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
        Schema::create('driver_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver_type'); // 'batangan' or 'cadangan'
            $table->integer('min_schedules')->default(11); // minimum schedules per period
            $table->integer('max_schedules')->default(14); // maximum schedules per period
            $table->integer('period_days')->default(15); // number of days in a period
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Make driver_type unique as we'll have one setting per driver type
            $table->unique('driver_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_schedule_settings');
    }
};
