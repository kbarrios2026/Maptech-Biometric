<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE attendances DROP CONSTRAINT IF EXISTS attendances_status_check");
        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_status_check CHECK (status IN ('Present', 'Late', 'Absent', 'Half Day', 'Leave', 'Overtime'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE attendances DROP CONSTRAINT IF EXISTS attendances_status_check");
        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_status_check CHECK (status IN ('Present', 'Late', 'Absent', 'Half Day', 'Leave'))");
    }
};
