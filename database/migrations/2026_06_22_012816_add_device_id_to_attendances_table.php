<?php

use App\Models\Attendance;
use App\Models\BiometricDevice;
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
        // First add the column as nullable
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('device_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('biometric_devices')
                ->nullOnDelete();
        });

        // Back-fill device_id for existing records where source matches a device name
        $devices = BiometricDevice::pluck('id', 'name');
        foreach ($devices as $name => $id) {
            Attendance::where('source', $name)
                ->whereNull('device_id')
                ->update(['device_id' => $id]);
        }

        // Add index for faster lookups
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropIndex(['device_id']);
            $table->dropColumn('device_id');
        });
    }
};
