<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('employment_type_id')->nullable()->after('position_id')->constrained('employment_types')->nullOnDelete();
            $table->foreignId('employee_status_id')->nullable()->after('employment_type_id')->constrained('employee_statuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['employment_type_id']);
            $table->dropForeign(['employee_status_id']);
            $table->dropColumn(['employment_type_id', 'employee_status_id']);
        });
    }
};
