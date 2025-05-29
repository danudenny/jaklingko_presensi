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
        Schema::create('schedule_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->comment('valid, invalid');
            $table->string('driver_type')->comment('batangan, cadangan, all');
            $table->integer('days')->comment('Number of days this pattern covers');
            $table->json('pattern')->comment('JSON array of shift codes: P, S, N');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['type', 'driver_type', 'days']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_patterns');
    }
};
