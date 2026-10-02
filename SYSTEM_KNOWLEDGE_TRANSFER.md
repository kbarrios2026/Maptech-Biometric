# Maptech Biometric Employee System

Knowledge-transfer guide for developers and administrators who maintain or operate the system.

## 1. System purpose

The application provides:

- User accounts and role-based access.
- Employee, department, position, employment type, and employee status management.
- Attendance recording and review.
- Human Resources (HR) DTR reports with date and employee filters, including printable reports.
- Employee self-service attendance viewing and printing.
- ZKTeco device registration, attendance synchronization, and device user mapping.
- Activity logging for selected administrative actions.

The application is built on Laravel 12, PHP 8.2 or later, and a relational database. The local development environment currently uses SQLite. The web interface is rendered with Blade and uses Bootstrap and Font Awesome.

## 2. User roles and access

Routes are protected by authentication middleware and the `role` middleware alias registered in `bootstrap/app.php`. Role names are stored in `roles`; a user's `role_id` determines the assigned role.

| Role | Current access |
| --- | --- |
| Super Admin | Dashboard; user, employee, department, position, and employment data management; daily attendance entry and changes; HR DTR and receipts; biometric devices; roles; company profile; settings; activity logs. |
| HR Admin | HR DTR and receipts; user, employee, department, position, employment type, employee status, and employee-device management. HR Admin is directed to the DTR after login. Daily quick attendance entry, biometric device administration, company profile, settings, roles, and activity logs are not available to HR Admin. |
| Employee | Employee attendance page for the employee profile linked to the authenticated account. Records can be filtered and printed, but not edited. |
| Payroll Officer | A role and permission set are seeded, but a dedicated payroll feature/route group is not currently implemented. |
| Attendance Officer | A role and permission set are seeded, but a dedicated attendance officer feature/route group is not currently implemented. |

The sidebar is only a navigation aid; the middleware on routes is the access-control boundary. When changing role access, update and test both the route middleware and the layout navigation.

## 3. Main application flows

### 3.1 Login and account management

1. `GET /login` displays the login form.
2. `POST /login` verifies credentials, regenerates the session, and records a login activity.
3. Employees are redirected to `/employee/attendance`.
4. HR Admins are redirected to `/admin/attendance/hr-admin`.
5. Other authenticated roles are redirected to `/admin/dashboard`.
6. Users can change their own password at `/change-password`.
7. Super Admin and HR Admin can manage system users. An administrator can set a new password from the user's edit page; the existing password cannot be viewed because passwords are hashed.

Seeded development account emails are `admin@example.com`, `hr@example.com`, `payroll@example.com`, `attendance@example.com`, and `employee@example.com`. The seeders set their initial password to `password`. These are development credentials only: change them before deploying or exposing the system.

### 3.2 Employee and organization data

- A `User` is a login account. It belongs to a `Role` and may have one linked `Employee`.
- An `Employee` stores the employee number, name, email, biometric identifier, department, position, joining date, status, and active state.
- A `Position` belongs to a `Department`.
- Employee create/edit forms filter positions by the selected department. The server also validates that the submitted position belongs to the submitted department.
- The biometric ID or a row in `employee_devices` connects a device PIN to an employee.

An employee account must be linked to an employee record to use the employee self-service page. The authenticated user is used to find that employee, so the page does not accept an employee ID from the URL or filter form.

### 3.3 Attendance and DTR

Attendance is stored per employee and date with optional check-in, check-out, status, source, notes, and overtime-review fields.

**Daily Attendance** (`/admin/attendance`) is Super Admin only. It lists records for a selected date and contains the quick attendance entry form. Status changes and overtime decisions are also protected by Super Admin route middleware.

**HR Admin DTR** (`/admin/attendance/hr-admin`) supports a date range, an optional employee filter, and status filters for Absent and Leave. The default range is the start of the current month through today; the end date is capped at today. The result is paginated.

For the HR DTR and printable report, the application adds display-only rows for missing weekdays:

- Only active employees with employment status Active or On Leave are included.
- Weekends are skipped.
- Dates before an employee's joining date are skipped.
- Existing attendance takes precedence.
- Missing days are displayed as Absent, or Leave for employees marked On Leave.
- These generated rows are not saved to the attendance table.

**Attendance receipts** (`/admin/attendance/receipt`) can be printed for one employee or all employees for the selected range. Use the browser's print dialog and select a printer or PDF destination.

**Employee attendance** (`/employee/attendance`) shows only recorded rows belonging to the signed-in employee. It supports a date range and browser printing. It has no attendance edit or update endpoint.

### 3.4 Attendance status rules

Status calculations are implemented in `app/Models/Attendance.php` and used by the device processing code:

- A check-in at or after 08:16 is Late.
- A check-in at or after 13:00 is Half Day.
- A checkout from 17:01 through 23:59 is treated as overtime, unless excluded by the record's status or rejected overtime decision.
- Overtime approval/rejection records the reviewing user, timestamp, and reason. Rejected overtime returns the attendance status to the status derived from check-in.

Review these rules with the business owner before changing the thresholds; status changes affect DTR and overtime reporting.

### 3.5 Biometric device synchronization

The system supports both the ZKTeco ADMS/PUSH flow and a legacy token webhook. Pull-mode support is provided through the ZKTeco service and the Super Admin device screen.

**ADMS/PUSH flow:**

1. A device calls `GET /iclock/cdata` to receive settings such as real-time transfer and timezone.
2. It sends tab-separated attendance records to `POST /iclock/cdata`.
3. The serial number must match an active registered biometric device; an unknown serial is not allowed to claim or send data to another device's record.
4. Device PINs are matched to an employee by biometric ID, employee ID, or employee-device mapping.
5. Attendance is consolidated into one record per employee per date. Earliest check-in and latest checkout are retained, including when multiple scans arrive together.
6. Device users may be imported from enrollment records.
7. The server responds with plain-text `OK`.
8. The device polls `GET /iclock/getrequest` for pending attendance queries and posts command results to `/iclock/devicecmd`.

The regular ADMS replay asks for the preceding seven days. To request a historical period of up to 31 days from an active push-mode device, use `php artisan biometric:replay-attendance {device-id} {YYYY-MM-DD} {YYYY-MM-DD}`. The one-time request is returned to the device at its next poll; the device must retain the punches and be able to reach the server. See [SETUP_ZKTECO_PUSH.md](SETUP_ZKTECO_PUSH.md) for LAN server binding and an example.

`POST /zkteco/webhook/{deviceToken}` accepts the legacy JSON webhook format. The token must identify an active device. The service parses common employee identifier and timestamp fields and processes each valid record.

The ADMS controller can auto-create an employee and employee-device mapping when it receives an unknown PIN. Such records should be reviewed and completed by HR. The auto-created login does not automatically receive the Employee role.

Refer to [SETUP_ZKTECO_PUSH.md](SETUP_ZKTECO_PUSH.md) for device setup instructions. Keep the device and server on a trusted network, and do not expose device endpoints to the public internet without an approved security design.

## 4. Data model overview

Key models and relationships:

| Model/table | Purpose |
| --- | --- |
| `User` / `users` | Login identity, password hash, and role link. |
| `Role` / `roles` | Role name and JSON permission list. Route middleware currently makes the access decision by role name. |
| `Employee` / `employees` | Employee profile linked to a user, department, position, and attendance. Employee records use soft deletes. |
| `Department` / `departments` | Organization unit containing positions and employees. |
| `Position` / `positions` | Job title linked to a department. |
| `Attendance` / `attendances` | Per-employee daily attendance, times, status, source, and overtime review data. |
| `EmployeeDevice` / `employee_devices` | Maps device identifiers/PINs to employee profiles and device names. |
| `BiometricDevice` / `biometric_devices` | Device connection and synchronization configuration, including serial number, mode, and webhook token. |
| `ActivityLog` / `activity_logs` | Audit entries for selected account, attendance, and administration actions. |

Schema changes belong in timestamped files under `database/migrations`; do not edit an already-applied migration to change a live database.

## 5. Source-code map

| Location | Responsibility |
| --- | --- |
| `routes/web.php` | Web routes, route names, authentication and role boundaries, device compatibility endpoints. |
| `app/Http/Middleware/CheckRole.php` | Role-name authorization middleware. |
| `app/Http/Controllers/Auth/` | Login, logout, and password changes. |
| `app/Http/Controllers/Admin/` | Admin and HR pages for users, employees, organization data, attendance, devices, and settings. |
| `app/Http/Controllers/Employee/` | Employee self-service pages. |
| `app/Http/Controllers/Device/` | ADMS and token webhook request handling. |
| `app/Services/ZktecoAttendanceService.php` | Normalizes and processes webhook attendance payloads. |
| `app/Services/ZktecoService.php` | Device connection, pull sync, and push configuration operations. |
| `app/Models/` | Database relationships, casts, and attendance status helpers. |
| `resources/views/` | Blade templates for the UI and printable reports. |
| `database/seeders/` | Development roles, settings, and sample login accounts. |
| `database/migrations/` | Database schema history. |
| `tests/Feature/` | HTTP/role behavior tests. |

## 6. Local development setup

Requirements:

- PHP 8.2 or later and required PHP extensions.
- Composer.
- Node.js and npm.
- SQLite for the default local setup, or a configured Laravel-supported database.

From the project directory:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

If `.env` already exists, do not overwrite it with the example file. Configure database values in `.env` before running migrations. If using SQLite, ensure the database file exists at the path configured by `DB_DATABASE`.

Run the Laravel application and frontend dev server in separate terminals:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
npm run dev
```

Open `http://127.0.0.1:8000`. The Composer `dev` script starts additional workers including Laravel Pail; Pail requires `pcntl`, which is unavailable in the default Windows PHP setup. On Windows, use the separate commands above instead.

To run tests:

```powershell
php artisan test
```

## 7. Configuration and operations

- `.env` contains environment-specific settings and must not be committed. `.env.example` is the safe template.
- `APP_NAME` controls the application name shown in reports and page titles. The current local value is `Maptech's Employee System`.
- `APP_URL` should match the URL reachable by users and devices.
- `DB_CONNECTION` and its associated variables select the database.
- `APP_DEBUG` should be false in production. Use a production-only `APP_KEY`, HTTPS, and strong account passwords.
- Do not reuse the demo password in any shared or production environment.
- Back up the active database regularly. For the local SQLite setup, back up `database/database.sqlite` while writes are stopped or by using a consistent SQLite backup method.
- Review `storage/logs/laravel.log` and the device-specific logs under `storage/logs/` when troubleshooting imports.
- Never run `php artisan migrate:fresh` on a database containing real employee or attendance records.

## 8. Troubleshooting

### Device records are not appearing

1. Confirm the device is active and its serial number/mode are correct.
2. Check its configured push URL and network reachability to `APP_URL`.
3. Confirm the PIN maps to `employees.biometric_id`, `employees.employee_id`, or an `employee_devices.device_identifier`.
4. Inspect `storage/logs/laravel.log` and device-related logs.
5. Check the device's last-sync time and the recent-attendance view.

### Employee cannot open My Attendance

1. Confirm the account has the Employee role.
2. Confirm that account is linked to an employee profile through `employees.user_id`.
3. Confirm attendance records exist for that employee and selected range.

### HR cannot access an admin page

This may be intentional. Review the role middleware in `routes/web.php` and navigation conditions in `resources/views/layouts/app.blade.php`. Do not rely on hiding a sidebar link alone to protect a route.

### Login form reports Page Expired (419)

Reload `/login` to receive a fresh CSRF token. The error commonly occurs when submitting a stale form after its session has expired or changed.

## 9. Change checklist for maintainers

Before changing or adding a feature:

1. Identify its route, controller, model, and view.
2. Check both route authorization and visible navigation.
3. Preserve employee ownership filtering for self-service pages.
4. Validate device and user input server-side; browser-side filtering is not authorization.
5. Add or update feature tests for changed role boundaries and attendance behavior.
6. Run `php artisan test` and compile Blade views with `php artisan view:cache`, then clear generated views with `php artisan view:clear`.
7. Update this guide and [USER_MANUAL.md](USER_MANUAL.md) when operational behavior changes.
