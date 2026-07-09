<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE attendances ALTER COLUMN status TYPE VARCHAR(20) USING status::text");
        }

        Schema::table('attendances', function (Blueprint $table): void {
            $table->text('overtime_reason')->nullable();
            $table->string('overtime_approval_status', 20)->nullable();
            $table->text('overtime_approval_reason')->nullable();
            $table->foreignId('overtime_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overtime_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('overtime_approved_by');
            $table->dropColumn([
                'overtime_reason',
                'overtime_approval_status',
                'overtime_approval_reason',
                'overtime_approved_at',
            ]);
        });
    }
};
