This folder is used by the `backup:site` Artisan command to temporarily store and write backups.

How to create a backup (from project root):

```bash
php artisan backup:site
```

What it saves:
- `storage/app` contents (recursively)
- `.env` file
- Database dump when a supported dump utility is available (`mysqldump` for MySQL, `pg_dump` for Postgres), or the SQLite file when using SQLite.

Backups are written as zip files to: `storage/backups/backup_YYYYMMDD_HHMMSS.zip`

Notes:
- Ensure the PHP process has permissions to read the storage directory and write into `storage/backups`.
- For MySQL/Postgres dumps, make sure `mysqldump`/`pg_dump` are installed and accessible in the PATH.
