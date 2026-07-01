<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo users with different roles
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $hrAdminRole = Role::where('name', 'HR Admin')->first();
        $payrollRole = Role::where('name', 'Payroll Officer')->first();
        $attendanceRole = Role::where('name', 'Attendance Officer')->first();
        $employeeRole = Role::where('name', 'Employee')->first();

        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id ?? null,
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr@example.com',
                'password' => Hash::make('password'),
                'role_id' => $hrAdminRole->id ?? null,
            ],
            [
                'name' => 'Payroll Staff',
                'email' => 'payroll@example.com',
                'password' => Hash::make('password'),
                'role_id' => $payrollRole->id ?? null,
            ],
            [
                'name' => 'Attendance Staff',
                'email' => 'attendance@example.com',
                'password' => Hash::make('password'),
                'role_id' => $attendanceRole->id ?? null,
            ],
            [
                'name' => 'John Doe',
                'email' => 'employee@example.com',
                'password' => Hash::make('password'),
                'role_id' => $employeeRole->id ?? null,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'role_id' => $userData['role_id'],
                ]
            );
        }
    }
}
