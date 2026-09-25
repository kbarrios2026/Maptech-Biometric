<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Models\User;
use App\Services\ZktecoWebService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PullDeviceUserDatabase extends Command
{
    protected $signature = 'device:pull-database {--device=1 : Device ID} {--force : Force pull even if recently synced}';

    protected $description = 'Pull the user database from a ZKTeco device';

    public function handle(): int
    {
        $device = BiometricDevice::find($this->option('device'));

        if (! $device) {
            $this->error('Device not found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && $device->last_synced_at?->diffInMinutes(now()) < 5) {
            $this->warn('Device was synced recently. Use --force to retry.');

            return self::SUCCESS;
        }

        if (! ZktecoWebService::testConnectivity($device->ip_address, $device->port ?? 8000)) {
            $this->error("Cannot reach device at {$device->ip_address}:{$device->port}");

            return self::FAILURE;
        }

        $remoteUsers = ZktecoWebService::pullUsersFromDevice($device->ip_address, $device->port ?? 8000);

        if ($remoteUsers === null) {
            $this->warn('The device does not expose a readable HTTP user API.');
            $this->line('Push-mode user records will be imported when the device sends OPERLOG USER records.');
            $device->update(['last_synced_at' => now()]);

            return self::SUCCESS;
        }

        $defaults = [
            'employment_type_id' => EmploymentType::firstOrCreate(['name' => 'Regular'], ['description' => 'Regular employee'])->id,
            'department_id' => Department::firstOrCreate(['department_name' => 'General'], ['description' => 'General department'])->id,
            'position_id' => Position::firstOrCreate(['position_name' => 'Staff'], ['description' => 'Staff position'])->id,
        ];

        $created = 0;
        $mapped = 0;

        foreach ($remoteUsers as $remoteUser) {
            $pin = trim((string) ($remoteUser['pin'] ?? $remoteUser['id'] ?? ''));
            if ($pin === '') {
                continue;
            }

            $name = trim((string) ($remoteUser['name'] ?? 'Device User'));
            $parts = preg_split('/\s+/', $name) ?: [];
            $firstName = $remoteUser['first_name'] ?? ($parts[0] ?? 'Device');
            $lastName = $remoteUser['last_name'] ?? (count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : "User {$pin}");

            $employee = Employee::query()
                ->where('biometric_id', $pin)
                ->orWhere('employee_id', $pin)
                ->first();

            if ($employee) {
                if (empty($employee->biometric_id)) {
                    $employee->forceFill(['biometric_id' => $pin])->save();
                }
                $mapped++;
            } else {
                $email = "device.{$pin}." . Str::slug($firstName.'-'.$lastName, '.') . '@maptech.local';
                $user = User::firstOrCreate(
                    ['email' => $email],
                    ['name' => trim($firstName.' '.$lastName), 'password' => Hash::make(Str::random(32))]
                );

                $employee = Employee::create(array_merge([
                    'user_id' => $user->id,
                    'employee_id' => 'BIO-'.$pin,
                    'biometric_id' => $pin,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'joining_date' => now()->toDateString(),
                    'employment_status' => 'Active',
                    'is_active' => true,
                ], $defaults));
                $created++;
            }

            EmployeeDevice::updateOrCreate(
                ['device_identifier' => $pin],
                ['employee_id' => $employee->id, 'device_name' => $device->name, 'is_primary' => true]
            );
        }

        $device->update(['last_synced_at' => now()]);
        $this->info("Imported {$created} new users and mapped {$mapped} existing users without renaming them.");

        return self::SUCCESS;
    }
}
