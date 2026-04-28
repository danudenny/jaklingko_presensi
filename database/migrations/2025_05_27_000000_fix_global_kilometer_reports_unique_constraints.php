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
            // Drop the old unique constraint if it exists
            $table->dropUnique('global_km_reports_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_kilometer_reports', function (Blueprint $table) {
            // Add back the old unique constraint
            $table->unique(['driver_id', 'unit_id', 'report_date', 'period'], 'global_km_reports_unique');
        });
    }
};
