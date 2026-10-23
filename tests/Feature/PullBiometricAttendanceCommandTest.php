<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Services\ZktecoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PullBiometricAttendanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_mode_device_is_not_sent_through_the_classic_pull_protocol(): void
    {
        $device = BiometricDevice::create([
            'name' => 'Push device',
            'ip_address' => '192.0.2.10',
            'sync_mode' => 'push',
        ]);

        $zkteco = Mockery::mock(ZktecoService::class);
        $zkteco->shouldNotReceive('pullAndProcessAttendance');
        $this->instance(ZktecoService::class, $zkteco);

        $this->artisan('biometric:pull', ['--device' => $device->id])
            ->expectsOutput('Device Push device is configured for push mode and cannot be pulled over the ZKTeco TCP/UDP protocol.')
            ->expectsOutput("Use biometric:replay-attendance {$device->id} <from> <to> to ask it to resend logs after it can reach and poll this server.")
            ->assertExitCode(1);
    }

    public function test_pull_command_fails_when_device_connection_fails(): void
    {
        $device = BiometricDevice::create([
            'name' => 'Pull device',
            'ip_address' => '192.0.2.11',
            'sync_mode' => 'pull',
        ]);

        $zkteco = Mockery::mock(ZktecoService::class);
        $zkteco->shouldReceive('pullAndProcessAttendance')
            ->once()
            ->with(Mockery::on(fn (BiometricDevice $actual) => $actual->is($device)))
            ->andReturn([
                'processed' => 0,
                'total' => 0,
                'errors' => ['Cannot connect to device.'],
            ]);
        $this->instance(ZktecoService::class, $zkteco);

        $this->artisan('biometric:pull', ['--device' => $device->id])
            ->expectsOutput('  [ERROR] Cannot connect to device.')
            ->assertExitCode(1);
    }

    public function test_pull_command_handles_no_devices_without_an_undefined_error_counter(): void
    {
        $this->artisan('biometric:pull', ['--all' => true])
            ->expectsOutput('No active pull-mode devices found.')
            ->assertSuccessful();
    }
}
