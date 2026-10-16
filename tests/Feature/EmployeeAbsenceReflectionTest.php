<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAbsenceReflectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_absence_reflection_and_admin_views_can_see_it(): void
    {
        Carbon::setTestNow('2026-10-16 10:00:00');

        try {
            $employeeRole = Role::create(['name' => 'Employee', 'permissions' => []]);
            $employeeUser = User::factory()->create(['role_id' => $employeeRole->id]);
            $employee = Employee::create([
                'user_id' => $employeeUser->id,
                'employee_id' => 'EMP-ABSENCE-REFLECTION',
                'first_name' => 'Absent',
                'last_name' => 'Employee',
                'email' => 'absent-employee@example.test',
                'joining_date' => '2026-01-01',
            ]);
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-15',
                'status' => 'Absent',
                'source' => 'HR Admin DTR',
            ]);

            $this->actingAs($employeeUser)
                ->post(route('employee.attendance.absence-reflection', $attendance), [
                    'absence_reflection' => 'I was unwell and notified my supervisor.',
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('attendances', [
                'id' => $attendance->id,
                'absence_reflection' => 'I was unwell and notified my supervisor.',
            ]);

            $this->get(route('employee.attendance.index', [
                'date_from' => '2026-10-15',
                'date_to' => '2026-10-15',
            ]))->assertOk()->assertSee('I was unwell and notified my supervisor.');

            $hrRole = Role::create(['name' => 'HR Admin', 'permissions' => []]);
            $hrUser = User::factory()->create(['role_id' => $hrRole->id]);
            $this->actingAs($hrUser)
                ->get(route('admin.attendance.hr-admin', [
                    'date_from' => '2026-10-15',
                    'date_to' => '2026-10-15',
                ]))
                ->assertOk()
                ->assertSee('I was unwell and notified my supervisor.');

            $this->get(route('admin.attendance.receipt', [
                'scope' => 'all',
                'date_from' => '2026-10-15',
                'date_to' => '2026-10-15',
            ]))->assertOk()->assertSee('I was unwell and notified my supervisor.');

            $adminRole = Role::create(['name' => 'Super Admin', 'permissions' => []]);
            $adminUser = User::factory()->create(['role_id' => $adminRole->id]);
            $this->actingAs($adminUser)
                ->get(route('admin.attendance.index', ['date' => '2026-10-15']))
                ->assertOk()
                ->assertSee('I was unwell and notified my supervisor.');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_employee_can_only_submit_reflections_for_their_own_absent_records(): void
    {
        $employeeRole = Role::create(['name' => 'Employee', 'permissions' => []]);
        $employeeUser = User::factory()->create(['role_id' => $employeeRole->id]);
        $otherUser = User::factory()->create(['role_id' => $employeeRole->id]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_id' => 'EMP-REFLECTION-OWNER',
            'first_name' => 'Reflection',
            'last_name' => 'Owner',
            'email' => 'reflection-owner@example.test',
            'joining_date' => '2026-01-01',
        ]);
        $otherEmployee = Employee::create([
            'user_id' => $otherUser->id,
            'employee_id' => 'EMP-REFLECTION-OTHER',
            'first_name' => 'Other',
            'last_name' => 'Employee',
            'email' => 'other-reflection@example.test',
            'joining_date' => '2026-01-01',
        ]);
        $otherAbsentAttendance = Attendance::create([
            'employee_id' => $otherEmployee->id,
            'attendance_date' => '2026-10-15',
            'status' => 'Absent',
        ]);
        $presentAttendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-15',
            'status' => 'Present',
        ]);

        $this->actingAs($employeeUser)
            ->post(route('employee.attendance.absence-reflection', $otherAbsentAttendance), [
                'absence_reflection' => 'This must not be saved.',
            ])
            ->assertNotFound();

        $this->post(route('employee.attendance.absence-reflection', $presentAttendance), [
            'absence_reflection' => 'This day was not absent.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'id' => $otherAbsentAttendance->id,
            'absence_reflection' => 'This must not be saved.',
        ]);
        $this->assertDatabaseMissing('attendances', [
            'id' => $presentAttendance->id,
            'absence_reflection' => 'This day was not absent.',
        ]);
    }

    public function test_absence_reflection_is_required_and_limited_to_two_thousand_characters(): void
    {
        $employeeRole = Role::create(['name' => 'Employee', 'permissions' => []]);
        $employeeUser = User::factory()->create(['role_id' => $employeeRole->id]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_id' => 'EMP-REFLECTION-VALIDATION',
            'first_name' => 'Validation',
            'last_name' => 'Employee',
            'email' => 'reflection-validation@example.test',
            'joining_date' => '2026-01-01',
        ]);
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-15',
            'status' => 'Absent',
        ]);

        $this->actingAs($employeeUser)
            ->from(route('employee.attendance.index'))
            ->post(route('employee.attendance.absence-reflection', $attendance), [
                'absence_reflection' => '',
            ])
            ->assertRedirect(route('employee.attendance.index'))
            ->assertSessionHasErrors('absence_reflection');

        $this->post(route('employee.attendance.absence-reflection', $attendance), [
            'absence_reflection' => str_repeat('a', 2001),
        ])->assertSessionHasErrors('absence_reflection');
    }
}
