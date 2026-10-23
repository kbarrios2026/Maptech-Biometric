<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_entry_adds_another_shift_for_an_existing_employee_date(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-ATTENDANCE-STORE',
            'first_name' => 'Attendance',
            'last_name' => 'Employee',
            'email' => 'attendance-store@example.test',
            'joining_date' => '2026-01-01',
        ]);

        DB::table('attendances')->insert([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-07 00:00:00',
            'check_in_time' => '08:01:41',
            'check_out_time' => '11:01:20',
            'status' => 'Present',
            'source' => 'ZKTeco',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('admin.attendance.store'), [
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-07',
                'check_in_time' => '08:00:11',
                'check_out_time' => '12:00:59',
                'status' => 'Present',
                'source' => 'Manual',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Attendance saved.');

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-07 00:00:00',
            'check_in_time' => '08:01:41',
            'check_out_time' => '11:01:20',
            'source' => 'ZKTeco',
        ]);
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('source', 'Manual')
            ->firstOrFail();
        $this->assertSame('2026-10-07', $attendance->attendance_date->toDateString());
        $this->assertSame('08:00:11', $attendance->check_in_time);
        $this->assertSame('12:00:59', $attendance->check_out_time);

        $this->actingAs($user)
            ->get(route('admin.attendance.index', ['date' => '2026-10-07']))
            ->assertOk()
            ->assertSee('08:00:11 AM')
            ->assertSee('12:00:59 PM')
            ->assertSee('step="1"', false);

        $this->actingAs($user)
            ->post(route('admin.attendance.store'), [
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-07',
                'check_in_time' => '07:15:11.000',
                'check_out_time' => '08:00:00',
                'status' => 'Present',
            ])
            ->assertSessionHasErrors('check_in_time');
    }

    public function test_manual_attendance_can_record_overnight_and_repeated_shifts(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-OVERNIGHT-SHIFT',
            'first_name' => 'Overnight',
            'last_name' => 'Employee',
            'email' => 'overnight-shift@example.test',
            'joining_date' => '2026-01-01',
        ]);

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-06',
            'check_in_time' => '07:15:11',
            'check_out_date' => '2026-10-07',
            'check_out_time' => '08:00:11',
            'status' => 'Overtime',
            'overtime_reason' => 'Overnight duty.',
        ])->assertRedirect()->assertSessionHas('success', 'Attendance saved.');

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-08',
            'check_in_time' => '08:01',
            'check_out_time' => '11:01',
            'status' => 'Present',
        ])->assertRedirect()->assertSessionHas('success', 'Attendance saved.');

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-08',
            'check_in_time' => '12:00',
            'check_out_time' => '13:00',
            'status' => 'Present',
        ])->assertRedirect()->assertSessionHas('success', 'Attendance saved.');

        $this->assertDatabaseCount('attendances', 3);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-06 00:00:00',
            'check_out_date' => '2026-10-07 00:00:00',
            'check_in_time' => '07:15:11',
            'check_out_time' => '08:00:11',
            'status' => 'Overtime',
        ]);
        $this->assertSame(2, Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-10-08')
            ->count());

        $this->actingAs($user)
            ->get(route('admin.attendance.hr-admin', [
                'date_from' => '2026-10-06',
                'date_to' => '2026-10-08',
            ]))
            ->assertOk()
            ->assertSee('Check-out Date')
            ->assertSee('Oct 07, 2026')
            ->assertSee('07:15:11 AM')
            ->assertSee('08:01:00 AM')
            ->assertSee('12:00:00 PM');
    }

    public function test_manual_attendance_applies_late_and_undertime_cutoffs(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-TIME-THRESHOLDS',
            'first_name' => 'Time',
            'last_name' => 'Thresholds',
            'email' => 'time-thresholds@example.test',
            'joining_date' => '2026-01-01',
        ]);

        foreach ([
            ['2026-10-12', '08:15:00', '16:00:00', null, 'Present'],
            ['2026-10-13', '08:15:01', '16:00:00', null, 'Late'],
            ['2026-10-14', '08:15:01', '15:59:59', null, 'Undertime'],
            ['2026-10-15', '08:15:01', '08:00:00', '2026-10-16', 'Late'],
        ] as [$date, $checkIn, $checkOut, $checkOutDate, $expectedStatus]) {
            $this->actingAs($user)->post(route('admin.attendance.store'), [
                'employee_id' => $employee->id,
                'attendance_date' => $date,
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'check_out_date' => $checkOutDate,
                'status' => 'Present',
            ])->assertSessionHas('success', 'Attendance saved.');

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $employee->id,
                'attendance_date' => $date . ' 00:00:00',
                'status' => $expectedStatus,
            ]);
        }
    }

    public function test_overnight_shift_prevents_absence_reconciliation_on_checkout_date(): void
    {
        Carbon::setTestNow('2026-10-09 09:00:00');

        try {
            $employee = Employee::create([
                'user_id' => User::factory()->create()->id,
                'employee_id' => 'EMP-OVERNIGHT-ABSENCE',
                'first_name' => 'Overnight',
                'last_name' => 'Worker',
                'email' => 'overnight-absence@example.test',
                'joining_date' => '2026-01-01',
            ]);

            Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-06',
                'check_out_date' => '2026-10-07',
                'check_in_time' => '07:15',
                'check_out_time' => '08:00',
                'status' => 'Overtime',
                'source' => 'Manual',
            ]);

            app(\App\Services\AttendanceAbsenceReconciler::class)
                ->reconcile('2026-10-07', '2026-10-07');

            $this->assertDatabaseCount('attendances', 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reconciliation_removes_generated_absence_when_attendance_is_later_recorded(): void
    {
        Carbon::setTestNow('2026-10-09 09:00:00');

        try {
            $employee = Employee::create([
                'user_id' => User::factory()->create()->id,
                'employee_id' => 'EMP-STALE-ABSENCE',
                'first_name' => 'Stale',
                'last_name' => 'Absence',
                'email' => 'stale-absence@example.test',
                'joining_date' => '2026-01-01',
            ]);

            DB::table('attendances')->insert([
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-06',
                'check_in_time' => null,
                'check_out_time' => null,
                'status' => 'Absent',
                'source' => 'Attendance Reconciliation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-06',
                'check_in_time' => '07:15:11',
                'check_out_time' => '08:00:11',
                'status' => 'Present',
                'source' => 'Manual',
            ]);

            app(\App\Services\AttendanceAbsenceReconciler::class)
                ->reconcile('2026-10-06', '2026-10-06');

            $this->assertDatabaseCount('attendances', 1);
            $this->assertDatabaseHas('attendances', [
                'employee_id' => $employee->id,
                'status' => 'Present',
                'source' => 'Manual',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_super_admin_can_delete_one_attendance_shift_without_deleting_other_shifts_that_day(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-DELETE-SHIFT',
            'first_name' => 'Delete',
            'last_name' => 'Shift',
            'email' => 'delete-shift@example.test',
            'joining_date' => '2026-01-01',
        ]);
        $firstShift = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-08',
            'check_in_time' => '08:01',
            'check_out_time' => '11:01',
            'status' => 'Present',
            'source' => 'Manual',
        ]);
        $secondShift = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-08',
            'check_in_time' => '12:00',
            'check_out_time' => '13:00',
            'status' => 'Present',
            'source' => 'Manual',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.attendance.destroy', $firstShift))
            ->assertRedirect(route('admin.attendance.index', ['date' => '2026-10-08']))
            ->assertSessionHas('success', 'Attendance record deleted.');

        $this->assertDatabaseMissing('attendances', ['id' => $firstShift->id]);
        $this->assertDatabaseHas('attendances', ['id' => $secondShift->id]);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendance_absence_suppressions', [
            'employee_id' => $firstShift->employee_id,
            'attendance_date' => '2026-10-08',
        ]);
    }

    public function test_hr_admin_cannot_delete_attendance_records(): void
    {
        $role = Role::create([
            'name' => 'HR Admin',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-DELETE-ACCESS',
            'first_name' => 'Delete',
            'last_name' => 'Access',
            'email' => 'delete-access@example.test',
            'joining_date' => '2026-01-01',
        ]);
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-08',
            'check_in_time' => '08:01',
            'status' => 'Present',
            'source' => 'Manual',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.attendance.destroy', $attendance))
            ->assertForbidden();

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_deleted_attendance_does_not_reappear_as_a_blank_absence(): void
    {
        Carbon::setTestNow('2026-10-19 11:30:00');

        try {
            $role = Role::create([
                'name' => 'Super Admin',
                'permissions' => [],
            ]);
            $user = User::factory()->create(['role_id' => $role->id]);
            $employee = Employee::create([
                'user_id' => User::factory()->create()->id,
                'employee_id' => 'EMP-DELETE-ABSENCE',
                'first_name' => 'Delete',
                'last_name' => 'Absence',
                'email' => 'delete-absence@example.test',
                'joining_date' => '2026-01-01',
            ]);
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => '2026-10-08',
                'check_in_time' => '08:00:00',
                'status' => 'Present',
                'source' => 'Manual',
            ]);

            $this->actingAs($user)
                ->delete(route('admin.attendance.destroy', $attendance))
                ->assertRedirect(route('admin.attendance.index', ['date' => '2026-10-08']))
                ->assertSessionHas('success', 'Attendance record deleted.');

            $this->get(route('admin.attendance.index', ['date' => '2026-10-08']))
                ->assertOk();

            $this->assertDatabaseCount('attendances', 0);
        } finally {
            Carbon::setTestNow();
        }
    }
}
