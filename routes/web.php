<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Device\IclockController;
use App\Http\Controllers\Device\ZktecoWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

// Device compatibility endpoints (non-/api paths used by some ZKTeco firmware)
Route::prefix('iclock')->name('iclock.compat.')->group(function () {
    Route::get('/cdata', [IclockController::class, 'cdata'])->name('cdata');
    Route::post('/cdata', [IclockController::class, 'postCdata'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('cdata.post');
    Route::get('/getrequest', [IclockController::class, 'getRequest'])->name('getrequest');
    Route::post('/devicecmd', [IclockController::class, 'deviceCmd'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('devicecmd');
});

Route::post('/zkteco/webhook/{deviceToken}', [ZktecoWebhookController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('zkteco.webhook.compat');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
    Route::get('/change-password', [LoginController::class, 'showChangePasswordForm'])->name('change-password');
    Route::post('/change-password', [LoginController::class, 'changePassword'])->name('change-password.post');
});

// Admin Routes - Protected by 'auth' middleware and role checks
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // System Users Management - Super Admin and HR Admin only
    Route::middleware('role:Super Admin,HR Admin')->group(function () {
        Route::resource('system-users', SystemUserController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('positions', PositionController::class);
        Route::resource('employees', EmployeeController::class);
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('attendance/{attendance}/overtime-review', [AttendanceController::class, 'reviewOvertime'])->name('attendance.overtime-review');
        Route::get('attendance/hr-admin', [AttendanceController::class, 'hrAdmin'])->name('attendance.hr-admin');
        Route::get('attendance/receipt', [AttendanceController::class, 'receipt'])->name('attendance.receipt');
    });

    // Personnel management
    Route::middleware('role:Super Admin,HR Admin')->group(function () {
        Route::resource('employment-types', \App\Http\Controllers\Admin\EmploymentTypeController::class);
        Route::resource('employee-statuses', \App\Http\Controllers\Admin\EmployeeStatusController::class);
        Route::resource('employee-devices', \App\Http\Controllers\Admin\EmployeeDeviceController::class);
    });

    // Biometric Devices
    Route::middleware('role:Super Admin,HR Admin')->group(function () {
        Route::resource('biometric-devices', \App\Http\Controllers\Admin\BiometricDeviceController::class);
        Route::post('biometric-devices/{biometricDevice}/test-connection', [\App\Http\Controllers\Admin\BiometricDeviceController::class, 'testConnection'])->name('biometric-devices.test-connection');
        Route::post('biometric-devices/{biometricDevice}/sync', [\App\Http\Controllers\Admin\BiometricDeviceController::class, 'syncAttendance'])->name('biometric-devices.sync');
        Route::post('biometric-devices/{biometricDevice}/enable-push', [\App\Http\Controllers\Admin\BiometricDeviceController::class, 'enablePush'])->name('biometric-devices.enable-push');
        Route::get('biometric-devices/{biometricDevice}/webhook-url', [\App\Http\Controllers\Admin\BiometricDeviceController::class, 'getWebhookUrl'])->name('biometric-devices.webhook-url');
        Route::get('biometric-devices/{biometricDevice}/recent-attendance', [\App\Http\Controllers\Admin\BiometricDeviceController::class, 'recentAttendance'])->name('biometric-devices.recent-attendance');
    });

    // Roles Management - Super Admin only
    Route::middleware('role:Super Admin')->group(function () {
        Route::resource('roles', RoleController::class);
    });

    // Activity Logs - Super Admin and HR Admin only
    Route::middleware('role:Super Admin,HR Admin')->group(function () {
        Route::resource('activity-logs', ActivityLogController::class, [
            'only' => ['index', 'show'],
        ]);
        // System settings and company profile
        Route::get('company/profile', [\App\Http\Controllers\Admin\CompanyProfileController::class, 'edit'])->name('company.edit');
        Route::post('company/profile', [\App\Http\Controllers\Admin\CompanyProfileController::class, 'update'])->name('company.update');
        Route::resource('settings', \App\Http\Controllers\Admin\SystemSettingsController::class)->only(['index', 'edit', 'update']);
    });
});
