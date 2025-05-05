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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ktp')->nullable()->unique();
            $table->string('kpp')->nullable();
            $table->string('type')->comment('batangan, cadangan');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active')->comment('aktif,nonaktif,cuti');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
