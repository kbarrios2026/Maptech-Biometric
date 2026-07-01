<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serial_number')->nullable()->unique();
            $table->string('ip_address')->nullable();
            $table->unsignedSmallInteger('port')->default(4370);
            $table->string('comm_key')->nullable();
            $table->string('device_token', 80)->unique();
            $table->enum('sync_mode', ['push', 'pull'])->default('push');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};
