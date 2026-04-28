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
        // Drop existing check constraint if exists
        DB::statement('ALTER TABLE maintenance_logs DROP CONSTRAINT IF EXISTS maintenance_logs_type_check');
        
        // Alter column to varchar
        DB::statement("ALTER TABLE maintenance_logs ALTER COLUMN type TYPE varchar(255)");
        
        // Add new check constraint
        DB::statement("ALTER TABLE maintenance_logs ADD CONSTRAINT maintenance_logs_type_check CHECK (type IN ('perbaikan', 'penggantian', 'tidak_ada_perbaikan'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop existing check constraint
        DB::statement('ALTER TABLE maintenance_logs DROP CONSTRAINT IF EXISTS maintenance_logs_type_check');
        
        // Add back original check constraint
        DB::statement("ALTER TABLE maintenance_logs ADD CONSTRAINT maintenance_logs_type_check CHECK (type IN ('perbaikan', 'penggantian'))");
    }
};
