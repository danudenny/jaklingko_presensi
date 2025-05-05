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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_number')->unique();
            $table->string('plate_number')->nullable();
            $table->string('unit_reg')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('kir')->nullable();
            $table->date('expired_stnk')->nullable();
            $table->date('expired_kir')->nullable();
            $table->date('expired_kp')->nullable();
            $table->string('status')->default('active')->comment('active, inactive,maintenance');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
