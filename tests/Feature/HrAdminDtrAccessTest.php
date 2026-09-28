<?php

namespace Tests\Feature;

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
}
