<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAttendanceTimeFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_admin_page_formats_attendance_times_without_seconds(): void
    {
        Carbon::setTestNow('2026-10-19 09:00:00');

        try {
            $role = Role::create([
                'name' => 'HR Admin',
                'permissions' => [],
            ]);
            $user = User::factory()->create(['role_id' => $role->id]);
            $employee = Employee::create([
                'user_id' => User::factory()->create()->id,
                'employee_id' => 'EMP-SHORT-TIME',
                'first_name' => 'Short',
                'last_name' => 'Time',
                'email' => 'short-time@example.test',
                'joining_date' => '2026-01-01',
            ]);

            Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-19',
                'check_in_time' => '08:00',
                'check_out_time' => '17:00',
                'status' => 'Present',
                'source' => 'Manual',
            ]);

            $this->actingAs($user)
                ->get(route('admin.attendance.hr-admin', [
                    'date_from' => '2026-10-19',
                    'date_to' => '2026-10-19',
                ]))
                ->assertOk()
                ->assertSee('08:00:00 AM')
                ->assertSee('05:00:00 PM');
        } finally {
            Carbon::setTestNow();
        }
    }
}
