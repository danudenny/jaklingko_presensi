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
        Schema::create('renops_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode')->default('manual'); // 'manual' or 'automatic'
            $table->decimal('saturday_threshold', 5, 2)->default(80.00); // percentage of units for Saturday
            $table->decimal('sunday_threshold', 5, 2)->default(70.00); // percentage of units for Sunday
            $table->decimal('holiday_threshold', 5, 2)->default(70.00); // percentage of units for holidays
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renops_settings');
    }
};
