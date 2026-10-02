<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
