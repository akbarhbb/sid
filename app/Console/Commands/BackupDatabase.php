<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:backup {--type=daily : Tipe backup (daily atau weekly) untuk penamaan dan retensi}')]
#[Description('Create a zip backup of the database and uploaded files (ledger evidence, organization photos)')]
class BackupDatabase extends Command
{
    private const int DAILY_RETENTION = 14;

    private const int WEEKLY_RETENTION = 4;

    public function handle(): int
    {
        $type = in_array($this->option('type'), ['daily', 'weekly'], true) ? (string) $this->option('type') : 'daily';
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir) && ! mkdir($backupDir, 0775, true) && ! is_dir($backupDir)) {
            $this->components->error('Cannot create backup directory.');

            return self::FAILURE;
        }

        $fileName = 'ruang-warga-'.$type.'-'.now()->format('Ymd-His').'.zip';
        $filePath = $backupDir.DIRECTORY_SEPARATOR.$fileName;

        $zip = new \ZipArchive();

        if ($zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->components->error('Cannot create zip archive.');

            return self::FAILURE;
        }

        $dbFile = $this->databaseFilePath();

        if ($dbFile !== null && is_file($dbFile)) {
            $zip->addFile($dbFile, 'database.sqlite');
        } else {
            $dumpPath = $this->dumpDatabase();

            if ($dumpPath === null) {
                $zip->close();
                @unlink($filePath);
                $this->components->error('Database dump failed.');

                return self::FAILURE;
            }
            $zip->addFile($dumpPath, 'database.sql');
        }

        foreach (['ledger-evidence', 'organization-photos'] as $directory) {
            $this->addDirectoryToZip($zip, storage_path('app/'.$directory), $directory);
        }

        $zip->close();

        if (isset($dumpPath) && is_string($dumpPath)) {
            @unlink($dumpPath);
        }

        $this->cleanupOldBackups($backupDir, $type);
        $this->components->info("Backup created: {$fileName}");

        return self::SUCCESS;
    }

    private function databaseFilePath(): ?string
    {
        if (config('database.default') !== 'sqlite') {
            return null;
        }

        $database = config('database.connections.sqlite.database');

        return $database === ':memory:' || ! is_string($database) ? null : $database;
    }

    private function dumpDatabase(): ?string
    {
        $path = storage_path('app/backups/database-dump-'.now()->format('YmdHis').'.sql');

        try {
            $handle = fopen($path, 'w');

            if ($handle === false) {
                return null;
            }

            $tables = collect(DB::select('SELECT name FROM sqlite_master WHERE type = ?', ['table']))
                ->pluck('name')
                ->reject(fn (string $name) => str_starts_with($name, 'sqlite_'))
                ->values();

            foreach ($tables as $table) {
                $createRow = DB::select('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table])[0] ?? null;

                if ($createRow === null) {
                    continue;
                }

                fwrite($handle, 'DROP TABLE IF EXISTS '.$this->quoteIdentifier($table).";\n");
                fwrite($handle, $createRow->sql.";\n");

                $rows = DB::table($table)->get();

                foreach ($rows as $row) {
                    $columns = array_keys((array) $row);
                    $values = array_map(fn ($value) => $value === null ? 'NULL' : DB::getPdo()->quote((string) $value), (array) $row);
                    fwrite($handle, 'INSERT INTO '.$this->quoteIdentifier($table).' ('.implode(', ', array_map($this->quoteIdentifier(...), $columns)).') VALUES ('.implode(', ', $values).");\n");
                }
            }

            fclose($handle);

            return $path;
        } catch (\Throwable) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);

            return null;
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $directory, string $archivePrefix): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $archivePrefix.'/'.$iterator->getSubPathname());
            }
        }
    }

    private function cleanupOldBackups(string $backupDir, string $type): void
    {
        $retention = $type === 'weekly' ? self::WEEKLY_RETENTION : self::DAILY_RETENTION;
        $backups = collect(glob($backupDir.DIRECTORY_SEPARATOR.'ruang-warga-'.$type.'-*.zip') ?: [])
            ->sort()
            ->values();

        $backups->take(max($backups->count() - $retention, 0))->each(fn (string $path) => @unlink($path));
    }
}
