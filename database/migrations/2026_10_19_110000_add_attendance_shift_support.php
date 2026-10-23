<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->date('check_out_date')->nullable()->after('attendance_date');
            $table->dropUnique('attendances_employee_id_attendance_date_unique');
            $table->index(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_employee_id_attendance_date_index');
            $table->unique(['employee_id', 'attendance_date']);
            $table->dropColumn('check_out_date');
        });
    }
};
