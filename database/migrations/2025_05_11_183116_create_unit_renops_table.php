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
        Schema::create('unit_renops', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->enum('day_type', ['saturday', 'sunday', 'holiday']);
            $table->foreignId('holiday_id')->nullable()->constrained('holidays')->onDelete('cascade');
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate entries for the same unit and date
            $table->unique(['date', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_renops');
    }
};
