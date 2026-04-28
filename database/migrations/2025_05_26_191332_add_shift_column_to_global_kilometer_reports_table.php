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
        Schema::table('global_kilometer_reports', function (Blueprint $table) {
            // Add the shift column
            $table->string('shift')->nullable()->after('report_date')->comment('pagi or siang');
            
            // Create a new unique index that includes shift
            $table->unique(['driver_id', 'unit_id', 'report_date', 'shift', 'period'], 'global_km_reports_shift_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_kilometer_reports', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('global_km_reports_shift_unique');
            
            // Drop the shift column
            $table->dropColumn('shift');
        });
    }
};
