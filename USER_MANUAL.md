# Maptech Biometric System User Manual

Version: 1.0  
Last updated: 2026-07-06

## 1. Purpose

This manual explains how to use the Maptech Biometric System for:
- managing users, roles, and employees
- configuring biometric devices
- recording and reviewing attendance
- viewing logs and generating attendance receipts

## 2. Who Should Use This Manual

- Super Admin
- HR Admin

Note: Most admin modules are available to Super Admin and HR Admin. Role management is Super Admin only.

## 3. Accessing the System

1. Open your system URL in a browser.
2. Enter your email and password.
3. Click Login.
4. After login, you are redirected to the Admin Dashboard.

### First Login (Seeded Accounts)

If the demo seeders are used, these default users exist:
- admin@example.com
- hr@example.com
- payroll@example.com
- attendance@example.com
- employee@example.com

Default password for seeded users: password

Important: Change passwords immediately after first login.

## 4. Security Basics

- Use strong passwords (minimum 8 characters).
- Change passwords from Change Password in your account area.
- Do not share accounts.
- Log out after use, especially on shared computers.

## 5. Dashboard Overview

The dashboard displays:
- total users
- total employees
- total departments
- active employees
- recent activity logs

Use this page for a quick operational snapshot.

## 6. Main Navigation and Modules

### 6.1 System Users

Purpose: Create and manage login accounts.

Typical actions:
1. Go to Admin > System Users.
2. Click Create.
3. Enter name, email, password, and role.
4. Save.

You can view, edit, and delete users from the list.

### 6.2 Roles

Purpose: Define permission groups.

Typical actions:
1. Go to Admin > Roles.
2. Click Create.
3. Enter role name and description.
4. Select permissions.
5. Save.

Important:
- You cannot delete a role if users are assigned to it.
- Role management is Super Admin only.

### 6.3 Departments

Purpose: Organize employees by business unit.

Typical actions:
1. Go to Admin > Departments.
2. Create a department with name, code, description, and active status.
3. Save.

Important:
- A department cannot be deleted while employees are assigned to it.

### 6.4 Positions

Purpose: Define job positions under departments.

Typical actions:
1. Go to Admin > Positions.
2. Create a position.
3. Select department.
4. Optionally set base salary and description.
5. Save.

Important:
- A position cannot be deleted while employees are assigned to it.

### 6.5 Employees

Purpose: Maintain employee profiles used for attendance and HR operations.

Key fields:
- system user link
- employee ID
- biometric ID
- department and position
- employment type and status
- joining date
- salary and active flag

Typical actions:
1. Go to Admin > Employees.
2. Click Create.
3. Fill required fields.
4. Save.

Notes:
- One system user can only be linked to one employee.
- Employee ID and email must be unique.
- Biometric ID should match the identifier pushed by the device.

### 6.6 Employment Types

Purpose: Classify employees (for example Regular, Contractual).

Actions:
- create, edit, delete employment type records

### 6.7 Employee Statuses

Purpose: Define statuses (for example Active, Probation, Resigned).

Actions:
- create, edit, delete status records

### 6.8 Employee Devices

Purpose: Map a device identifier to an employee.

Typical actions:
1. Go to Admin > Employee Devices.
2. Create mapping with employee and device identifier.
3. Set as primary if needed.
4. Save.

Notes:
- Device identifier must be unique.
- If a mapping is marked primary, other mappings for that employee are automatically set non-primary.

### 6.9 Biometric Devices

Purpose: Register and manage ZKTeco devices.

Fields:
- name
- serial number
- IP address
- port
- communication key
- sync mode (push or pull)
- active status

Typical actions:
1. Go to Admin > Biometric Devices.
2. Create or edit a device.
3. Choose sync mode.
4. Save and open device details.

Available device actions on the details page:
- test connection
- sync attendance (pull mode)
- enable push mode
- view webhook URL and connection guide
- monitor recent attendance feed

### 6.10 Attendance

There are three attendance views:

1. Daily Attendance (Admin > Attendance)
- filter by date
- view employee check-ins and check-outs
- add or update manual attendance

2. HR Attendance View (Admin > Attendance HR)
- filter by date range
- optional filter by employee
- view totals for Present, Late, Overtime, and Others
- paginate through records

3. Attendance Receipt
- generate printable summary by date range
- single employee or all employees scope

Manual attendance statuses:
- Present
- Late
- Absent
- Half Day
- Leave
- Overtime

Overtime workflow:
- overtime may be marked manually or auto-detected from late checkout
- overtime records can be approved or rejected with reason

### 6.11 Company Profile

Purpose: Store organization information used across the system.

Fields:
- name
- address
- phone
- email
- website
- logo

### 6.12 System Settings

Purpose: Manage system-wide key/value configuration.

Typical actions:
1. Go to Admin > Settings.
2. Open a key.
3. Update value.
4. Save.

Examples of stored settings:
- app_name
- company_name
- attendance_grace_period_minutes
- late_threshold_minutes
- default_shift_start
- default_shift_end
- device_server_host
- device_server_port
- webhook_base_url

### 6.13 Activity Logs

Purpose: Audit who changed what and when.

Features:
- filter by date range
- filter by user
- filter by action
- open details for old and new values

## 7. Biometric Device Setup (Push Mode)

This system supports ADMS endpoints used by many ZKTeco devices.

Core endpoints:
- GET /iclock/cdata
- POST /iclock/cdata
- GET /iclock/getrequest
- POST /iclock/devicecmd
- POST /zkteco/webhook/{deviceToken}

Recommended setup flow:
1. Register the device in Biometric Devices.
2. Set sync mode to push.
3. Copy the webhook or ADMS URL from device details.
4. Configure the URL in the device web admin.
5. Ensure the Laravel server is reachable from the LAN.
6. Verify logs and recent attendance feed.

Important network rule:
- Do not use localhost or 127.0.0.1 in device settings.
- Use LAN IP or public domain that the device can reach.

## 8. Daily Operations Checklist

1. Confirm devices are active and connected.
2. Check Attendance pages for missing or late entries.
3. Review and decide pending overtime.
4. Review Activity Logs for unusual changes.
5. Export or print attendance receipt if needed.

## 9. Troubleshooting

### 9.1 Settings Page Is Empty

- Ensure database has seeded settings.
- Run seeding command:
  php artisan db:seed

### 9.2 Device Not Sending Logs

- Verify correct push URL.
- Verify server is reachable on network.
- Start server on all interfaces if using artisan serve:
  php artisan serve --host=0.0.0.0 --port=8000
- Check Laravel logs in storage/logs/laravel.log.

### 9.3 Login Failed

- Confirm correct email/password.
- Check user role assignment.
- Reset password as Super Admin if needed.

### 9.4 Cannot Delete Department or Position

- Remove or reassign related employees first.

### 9.5 Cannot Delete Role

- Reassign users from that role before deletion.

## 10. Data and Audit Best Practices

- Keep employee and biometric IDs consistent.
- Use primary device mappings correctly.
- Record reasons for overtime decisions.
- Review activity logs regularly.
- Avoid direct database edits for operational data.

## 11. Quick Command Reference (For Administrators)

- Run migrations:
  php artisan migrate

- Seed default data:
  php artisan db:seed

- Serve locally for LAN testing:
  php artisan serve --host=0.0.0.0 --port=8000

- Tail recent logs in PowerShell:
  Get-Content storage/logs/laravel.log -Tail 100

## 12. Support and Escalation

When reporting an issue, provide:
- module name (for example Attendance or Biometric Devices)
- exact time of issue
- affected user or employee
- screenshot and error message
- relevant log lines from laravel.log

This information speeds up diagnosis and resolution.
