<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BackupSite extends Command
{
    protected $signature = 'backup:site';
    protected $description = 'Create a site backup (storage, .env, and DB dump when available)';

    public function handle()
    {
        $this->info('Starting backup...');

        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $tmpDir = $backupDir . DIRECTORY_SEPARATOR . 'tmp_' . $timestamp;
        mkdir($tmpDir);

        // Copy .env
        if (file_exists(base_path('.env'))) {
            copy(base_path('.env'), $tmpDir . DIRECTORY_SEPARATOR . '.env');
        }

        // Copy storage/app
        $storageSource = storage_path('app');
        $storageTarget = $tmpDir . DIRECTORY_SEPARATOR . 'storage_app';
        $this->recurseCopy($storageSource, $storageTarget);

        // Try DB dump for mysql or sqlite
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if ($connection === 'sqlite') {
            $path = $dbConfig['database'] ?? database_path('database.sqlite');
            if (file_exists($path)) {
                copy($path, $tmpDir . DIRECTORY_SEPARATOR . basename($path));
            }
        } elseif (in_array($connection, ['mysql', 'pgsql'])) {
            // attempt to run mysqldump/pg_dump if available
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? ($connection === 'mysql' ? 3306 : 5432);
            $database = $dbConfig['database'] ?? '';
            $username = $dbConfig['username'] ?? '';
            $password = $dbConfig['password'] ?? '';

            if ($connection === 'mysql' && $this->commandExists('mysqldump')) {
                $dumpFile = $tmpDir . DIRECTORY_SEPARATOR . 'db_' . $timestamp . '.sql';
                $cmd = "mysqldump -h{$host} -P{$port} -u{$username} -p'{$password}' {$database} > " . escapeshellarg($dumpFile);
                @shell_exec($cmd);
                if (file_exists($dumpFile)) {
                    $this->info('MySQL dump completed.');
                }
            } elseif ($connection === 'pgsql' && $this->commandExists('pg_dump')) {
                $dumpFile = $tmpDir . DIRECTORY_SEPARATOR . 'db_' . $timestamp . '.sql';
                $cmd = "pg_dump -h {$host} -p {$port} -U {$username} -F c -b -v -f " . escapeshellarg($dumpFile) . ' ' . escapeshellarg($database);
                putenv("PGPASSWORD={$password}");
                @shell_exec($cmd);
                if (file_exists($dumpFile)) {
                    $this->info('Postgres dump completed.');
                }
            } else {
                $this->warn('DB dump not available on this host or dump binary missing.');
            }
        }

        // Create zip
        $zipFile = $backupDir . DIRECTORY_SEPARATOR . 'backup_' . $timestamp . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            $this->addFolderToZip($tmpDir, $zip, strlen($tmpDir) + 1);
            $zip->close();
            $this->info('Backup zip created at: ' . $zipFile);
        } else {
            $this->error('Failed to create zip file.');
        }

        // Cleanup tmp
        $this->recurseRemove($tmpDir);

        $this->info('Backup completed.');
        return 0;
    }

    protected function commandExists($cmd)
    {
        $which = (stripos(PHP_OS, 'WIN') === 0) ? 'where' : 'which';
        $process = @shell_exec("{$which} " . escapeshellarg($cmd));
        return !empty($process);
    }

    protected function recurseCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                $s = $src . DIRECTORY_SEPARATOR . $file;
                $d = $dst . DIRECTORY_SEPARATOR . $file;
                if (is_dir($s)) {
                    $this->recurseCopy($s, $d);
                } else {
                    @copy($s, $d);
                }
            }
        }
        closedir($dir);
    }

    protected function addFolderToZip($folder, \ZipArchive $zip, $baseLength)
    {
        $handle = opendir($folder);
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') continue;
            $path = $folder . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->addFolderToZip($path, $zip, $baseLength);
            } else {
                $localName = substr($path, $baseLength);
                $zip->addFile($path, $localName);
            }
        }
        closedir($handle);
    }

    protected function recurseRemove($dir)
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($dir);
    }
}
