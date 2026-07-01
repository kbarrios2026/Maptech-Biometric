<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'description' => 'Super Administrator with full access',
                'permissions' => [
                    'view_dashboard',
                    'manage_users',
                    'manage_roles',
                    'manage_employees',
                    'manage_departments',
                    'manage_positions',
                    'manage_biometric_devices',
                    'manage_attendance',
                    'manage_schedules',
                    'manage_leaves',
                    'manage_payroll',
                    'view_reports',
                    'view_activity_logs',
                ],
            ],
            [
                'name' => 'HR Admin',
                'description' => 'HR Administrator with HR management access',
                'permissions' => [
                    'view_dashboard',
                    'manage_employees',
                    'manage_departments',
                    'manage_positions',
                    'manage_schedules',
                    'manage_leaves',
                    'view_reports',
                    'view_activity_logs',
                ],
            ],
            [
                'name' => 'Payroll Officer',
                'description' => 'Payroll Officer with payroll management access',
                'permissions' => [
                    'view_dashboard',
                    'manage_employees',
                    'manage_payroll',
                    'view_reports',
                    'view_activity_logs',
                ],
            ],
            [
                'name' => 'Attendance Officer',
                'description' => 'Attendance Officer with attendance management access',
                'permissions' => [
                    'view_dashboard',
                    'manage_biometric_devices',
                    'manage_attendance',
                    'view_reports',
                    'view_activity_logs',
                ],
            ],
            [
                'name' => 'Employee',
                'description' => 'Regular Employee with limited access',
                'permissions' => [
                    'view_dashboard',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'description' => $roleData['description'],
                    'permissions' => $roleData['permissions'],
                ]
            );
        }
    }
}
