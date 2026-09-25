         # Laravel Project - Copilot Instructions

This is a Laravel 12 project for biometric application development.

## Project Structure
- `app/` - Application logic (models, controllers, services)
- `routes/` - Route definitions
- `database/` - Migrations and seeders
- `resources/views/` - Blade templates
- `config/` - Configuration files

## Setup Instructions

1. **Environment Configuration**
   - Copy `.env.example` to `.env` (already done)
   - Update `APP_KEY` by running: `php artisan key:generate`
   - Configure database in `.env`

2. **Database Setup**
   - Run migrations: `php artisan migrate`
   - Seed data: `php artisan db:seed` (if seeders exist)

3. **Run Development Server**
   - `php artisan serve`
   - Application will be available at http://localhost:8000

## Common Commands
- `php artisan make:model ModelName -mcr` - Create model with migration, controller, and resource
- `php artisan make:migration create_table_name` - Create migration
- `php artisan tinker` - Interactive shell
- `php artisan route:list` - List all routes
- `php artisan config:cache` - Cache configuration

## Development Workflow
- Use `php artisan` commands for scaffolding
- Follow Laravel conventions for models, controllers, and views
- Use migrations for database changes
- Keep code DRY and follow SOLID principles
