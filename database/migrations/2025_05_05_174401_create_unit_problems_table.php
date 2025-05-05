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
        Schema::create('unit_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('driver_id')->constrained('drivers');
            $table->date('date_reported');
            $table->time('time_reported');
            $table->string('shift')->nullable();
            $table->text('description');
            $table->string('location')->nullable();
            $table->boolean('on_schedule')->default(false);
            $table->foreignId('schedule_history_id')->nullable()->constrained('driver_schedule_history');
            $table->timestamps();
        });

        // Create table for storing photos
        Schema::create('unit_problem_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_problem_id')->constrained('unit_problems')->onDelete('cascade');
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_problem_photos');
        Schema::dropIfExists('unit_problems');
    }
};
