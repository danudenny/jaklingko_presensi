<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        });
        
        // Create a new unique index that includes shift, without dropping the old one first
        DB::statement('ALTER TABLE global_kilometer_reports ADD UNIQUE KEY global_km_reports_shift_unique (driver_id, unit_id, report_date, shift, period)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint
        DB::statement('ALTER TABLE global_kilometer_reports DROP INDEX global_km_reports_shift_unique');
        
        Schema::table('global_kilometer_reports', function (Blueprint $table) {
            // Drop the shift column
            $table->dropColumn('shift');
        });
    }
};
