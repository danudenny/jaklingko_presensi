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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->date('date_reported');
            $table->time('time_reported');
            $table->text('description');
            $table->enum('type', ['perbaikan', 'penggantian']);
            $table->string('parts')->nullable();
            $table->enum('category', ['baru', 'bekas'])->nullable();
            $table->string('source_of_sparepart')->nullable();
            $table->json('costs')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->boolean('on_schedule')->default(false);
            $table->foreignId('schedule_history_id')->nullable()->constrained('driver_schedule_history')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
