<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HrAdminDtrAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_admin_lands_on_the_dtr_and_sees_only_allowed_navigation(): void
    {
        $role = Role::create([
            'name' => 'HR Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.attendance.hr-admin'));

        $this->actingAs($user)
            ->get(route('admin.attendance.hr-admin'))
            ->assertOk()
            ->assertSee('HR Admin DTR')
            ->assertSee('Live updates refresh every 30 seconds.')
            ->assertSee('window.setInterval', false)
            ->assertDontSee('Quick Attendance Entry')
            ->assertDontSee('Biometric Devices')
            ->assertDontSee('Company Profile')
            ->assertDontSee('Activity Logs')
            ->assertDontSee('Settings')
            ->assertDontSee('Roles');
    }

    public function test_opening_hr_admin_dtr_queues_recent_attendance_replay_for_push_devices(): void
    {
        Carbon::setTestNow('2026-10-05 13:20:00');
        $serialNumber = 'HR-REPLAY-DEVICE';
        $replayKey = "zkteco.attlog-replay.{$serialNumber}";
        $throttleKey = "zkteco.attlog-auto-refresh.{$serialNumber}";
        Cache::forget($replayKey);
        Cache::forget($throttleKey);

        try {
            BiometricDevice::create([
                'name' => 'Test push device',
                'serial_number' => $serialNumber,
                'sync_mode' => 'push',
                'is_active' => true,
            ]);

            $role = Role::create([
                'name' => 'HR Admin',
                'permissions' => [],
            ]);
            $user = User::factory()->create(['role_id' => $role->id]);

            $this->actingAs($user)
                ->get(route('admin.attendance.hr-admin'))
                ->assertOk();

            $this->assertSame([
                'from' => '2026-09-28',
                'to' => '2026-10-05',
            ], Cache::get($replayKey));

            $this->get('/iclock/getrequest?SN='.$serialNumber)
                ->assertOk()
                ->assertSee('StartTime=2026-09-28 00:00:00', false)
                ->assertSee('EndTime=2026-10-05 23:59:59', false);
        } finally {
            Cache::forget($replayKey);
            Cache::forget($throttleKey);
            Carbon::setTestNow();
        }
    }

    public function test_hr_admin_cannot_access_super_admin_attendance_or_system_routes(): void
    {
        $role = Role::create([
            'name' => 'HR Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        foreach ([
            '/admin/attendance',
            '/admin/biometric-devices',
            '/admin/company/profile',
            '/admin/settings',
            '/admin/roles',
            '/admin/activity-logs',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_hr_admin_calendar_shows_each_date_and_does_not_create_missing_attendance(): void
    {
        $role = Role::create([
            'name' => 'HR Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-CALENDAR-1',
            'first_name' => 'Calendar',
            'last_name' => 'Employee',
            'email' => 'calendar-employee@example.test',
            'joining_date' => '2026-01-01',
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-03',
            'check_in_time' => '08:00:00',
            'status' => 'Present',
            'source' => 'Test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.attendance.hr-admin', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('Daily Attendance Calendar')
            ->assertSee('No saved records')
            ->assertSee('July 31, 2026')
            ->assertSee('1 record');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_hr_admin_status_filter_and_summary_include_half_day_records(): void
    {
        $role = Role::create([
            'name' => 'HR Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-HALF-DAY-1',
            'first_name' => 'Half',
            'last_name' => 'Day',
            'email' => 'half-day-employee@example.test',
            'joining_date' => '2026-01-01',
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-15',
            'check_in_time' => '13:00:00',
            'status' => 'Half Day',
            'source' => 'Test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.attendance.hr-admin', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('value="Half Day"', false)
            ->assertSee('1 record');
    }

    public function test_hr_admin_does_not_generate_placeholder_absent_rows_for_missing_dates(): void
    {
        $role = Role::create([
            'name' => 'HR Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('admin.attendance.hr-admin', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertDontSee('DTR status')
            ->assertSee('No saved records');
    }
}
