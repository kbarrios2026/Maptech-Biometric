# Maptech Biometric Employee System

Web-based employee, attendance, and ZKTeco biometric device management system built with Laravel.

## Start here

- [System knowledge-transfer documentation](SYSTEM_KNOWLEDGE_TRANSFER.md) - architecture, roles, attendance data flow, setup, and maintenance.
- [User manual](USER_MANUAL.md) - day-to-day instructions for system users.
- [ZKTeco PUSH setup](SETUP_ZKTECO_PUSH.md) - configure devices to send attendance to the server.

## Quick start

Requirements: PHP 8.2+, Composer, Node.js/npm, and SQLite (or another Laravel-supported database).

From the project directory:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

If Composer is not installed globally but `composer.phar` is present in the project, use `php composer.phar` in place of `composer`.

For local development, run these in separate terminals:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
npm run dev
```

Open `http://127.0.0.1:8000`. Seeded development accounts use the password `password`; change it before using any non-development environment.

## Tests

```powershell
php artisan test
```

Do not run `migrate:fresh` against a database containing employee or attendance data. See the knowledge-transfer document for database backup and operational guidance.
