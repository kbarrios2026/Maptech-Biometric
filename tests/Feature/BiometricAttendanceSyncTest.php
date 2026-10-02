<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\User;
use App\Services\ZktecoAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BiometricAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_replay_window_includes_previous_month_checkouts(): void
    {
        Carbon::setTestNow('2026-10-01 09:00:00');
        Cache::forget('zkteco.query-attlog.test-device');
        BiometricDevice::create([
            'name' => 'Test device',
            'serial_number' => 'test-device',
            'sync_mode' => 'push',
        ]);

        try {
            $this->get('/iclock/getrequest?SN=test-device')
                ->assertOk()
                ->assertSee('StartTime=2026-09-24 00:00:00', false)
                ->assertSee('EndTime=2026-10-01 09:00:00', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_unknown_iClock_serial_cannot_reassign_a_registered_device(): void
    {
        $device = BiometricDevice::create([
            'name' => 'Test device',
            'serial_number' => 'REGISTERED-SERIAL',
            'sync_mode' => 'push',
        ]);

        $this->get('/iclock/cdata?SN=browser-request')
            ->assertOk()
            ->assertSee('GET OPTION FROM: browser-request', false);

        $this->assertDatabaseHas('biometric_devices', [
            'id' => $device->id,
            'serial_number' => 'REGISTERED-SERIAL',
        ]);

        $this->call(
            'POST',
            '/iclock/cdata?SN=browser-request&table=ATTLOG',
            server: ['CONTENT_TYPE' => 'text/plain'],
            content: "12345\t2026-07-01 08:00:00\t0\t1\t0\t0"
        )->assertOk();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_adms_batch_merges_multiple_scans_for_the_same_employee_and_date(): void
    {
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-ADMS-BATCH',
            'first_name' => 'Batch',
            'last_name' => 'Employee',
            'email' => 'batch-employee@example.test',
            'joining_date' => '2026-01-01',
            'biometric_id' => '54321',
        ]);
        BiometricDevice::create([
            'name' => 'Test device',
            'serial_number' => 'BATCH-DEVICE',
            'sync_mode' => 'push',
        ]);

        $this->call(
            'POST',
            '/iclock/cdata?SN=BATCH-DEVICE&table=ATTLOG',
            server: ['CONTENT_TYPE' => 'text/plain'],
            content: implode("\r\n", [
                "54321\t2026-07-06 08:40:00\t0\t1\t0\t0",
                "54321\t2026-07-06 08:05:00\t0\t1\t0\t0",
                "54321\t2026-07-06 17:20:00\t1\t1\t0\t0",
            ])
        )->assertOk();

        $this->assertDatabaseCount('attendances', 1);
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-07-06')
            ->firstOrFail();

        $this->assertSame('08:05:00', $attendance->check_in_time);
        $this->assertSame('17:20:00', $attendance->check_out_time);
        $this->assertSame('Overtime', $attendance->status);
    }

    public function test_queued_historical_replay_is_returned_to_the_registered_device(): void
    {
        Carbon::setTestNow('2026-10-02 13:00:00');
        $device = BiometricDevice::create([
            'name' => 'Test device',
            'serial_number' => 'REPLAY-DEVICE',
            'sync_mode' => 'push',
        ]);

        $this->artisan('biometric:replay-attendance', [
            'device' => $device->id,
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ])->assertSuccessful();

        $this->get('/iclock/getrequest?SN=REPLAY-DEVICE')
            ->assertOk()
            ->assertSee('StartTime=2026-07-01 00:00:00', false)
            ->assertSee('EndTime=2026-07-31 23:59:59', false);

        $this->assertNull(Cache::get('zkteco.attlog-replay.REPLAY-DEVICE'));
        Carbon::setTestNow();
    }

    public function test_checkout_only_device_event_is_stored_as_checkout(): void
    {
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-TEST-1',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'test-employee@example.test',
            'joining_date' => '2026-01-01',
            'biometric_id' => '12345',
        ]);
        $device = BiometricDevice::create([
            'name' => 'Test device',
            'serial_number' => 'TEST-SERIAL',
        ]);

        app(ZktecoAttendanceService::class)->processLogs($device, [[
            'biometric_id' => '12345',
            'timestamp' => '2026-09-30 18:00:00',
            'status' => 1,
        ]]);

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-09-30')
            ->firstOrFail();

        $this->assertNull($attendance->check_in_time);
        $this->assertSame('18:00:00', $attendance->check_out_time);
        $this->assertSame('Overtime', $attendance->status);
    }

    public function test_unknown_device_pins_are_skipped_instead_of_creating_placeholder_employees(): void
    {
        BiometricDevice::create([
            'name' => 'Test device',
            'serial_number' => 'TEST-SERIAL',
        ]);

        $this->call(
            'POST',
            '/iclock/cdata?SN=TEST-SERIAL&table=ATTLOG',
            server: ['CONTENT_TYPE' => 'text/plain'],
            content: "999\t2026-09-30 18:00:00\t1\t1\t0\t0"
        )->assertOk();

        $this->call(
            'POST',
            '/iclock/cdata?SN=TEST-SERIAL&table=ENROLL_USER',
            server: ['CONTENT_TYPE' => 'text/plain'],
            content: "USER PIN=999 Name=Unknown Pri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000000"
        )->assertOk();

        $this->assertDatabaseMissing('employees', ['biometric_id' => '999']);
        $this->assertDatabaseMissing('employee_devices', ['device_identifier' => '999']);
        $this->assertDatabaseCount('attendances', 0);
    }
}
