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
        // Drop the old unique constraint
        DB::statement('ALTER TABLE global_kilometer_reports DROP INDEX global_km_reports_unique');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old unique constraint
        DB::statement('ALTER TABLE global_kilometer_reports ADD UNIQUE KEY global_km_reports_unique (driver_id, unit_id, report_date, period)');
    }
};
