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
        Schema::create('kilometer_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('route_id')->constrained('routes');
            $table->date('date');
            $table->double('kilometers', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate entries
            $table->unique(['unit_id', 'route_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kilometer_reports');
    }
};
