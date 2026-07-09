<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'app_name' => 'Maptech Biometric',
            'company_name' => 'Maptech',
            'attendance_grace_period_minutes' => '10',
            'late_threshold_minutes' => '15',
            'overtime_enabled' => '1',
            'default_shift_start' => '09:00',
            'default_shift_end' => '18:00',
            'device_server_host' => '192.168.1.50',
            'device_server_port' => '8000',
            'device_push_enabled' => '1',
            'webhook_base_url' => 'http://192.168.1.50:8000',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}