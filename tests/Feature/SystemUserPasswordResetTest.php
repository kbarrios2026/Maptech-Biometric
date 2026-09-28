<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemUserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_a_new_password_without_logging_the_password(): void
    {
        $adminRole = Role::create(['name' => 'Super Admin']);
        $employeeRole = Role::create(['name' => 'Employee']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $target = User::factory()->create([
            'role_id' => $employeeRole->id,
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.system-users.password', $target), [
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('admin.system-users.edit', $target))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $target->fresh()->password));
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'reset_user_password',
            'model' => 'User',
            'model_id' => $target->id,
        ]);
        $this->assertStringNotContainsString(
            'new-password-123',
            ActivityLog::query()->latest()->firstOrFail()->toJson()
        );
    }

    public function test_password_reset_requires_confirmation_and_does_not_change_password_when_invalid(): void
    {
        $adminRole = Role::create(['name' => 'Super Admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $target = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($admin)
            ->from(route('admin.system-users.edit', $target))
            ->post(route('admin.system-users.password', $target), [
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors(['password']);

        $this->assertTrue(Hash::check('old-password', $target->fresh()->password));
    }
}
