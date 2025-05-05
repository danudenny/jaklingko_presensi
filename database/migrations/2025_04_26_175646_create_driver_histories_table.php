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
        Schema::create('driver_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->string('shift')->nullable()->comment('pagi. siang');
            $table->boolean('as_backup')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('as_renops')->default(false);
            $table->boolean('on_leave')->default(false);
            $table->boolean('on_duty')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_histories');
    }
};
