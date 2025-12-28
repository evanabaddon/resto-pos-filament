<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');

        // Ensure backup directory exists
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a database backup
     */
    public function createBackup(): string
    {
        $filename = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $filepath = $this->backupPath . '/' . $filename;

        try {
            $database = config('database.connections.mysql.database');

            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;

            $sql = "-- Database Backup\n";
            $sql .= "-- Created: " . now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // Drop table
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

                // Create table
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                $sql .= $createTable->{'Create Table'} . ";\n\n";

                // Insert data
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array)$row as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Write to file
            File::put($filepath, $sql);

            Log::info('Database backup created', ['filename' => $filename]);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Backup creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . $filename;

        if (!File::exists($filepath)) {
            throw new \Exception('Backup file not found: ' . $filename);
        }

        try {
            // Read SQL file
            $sql = File::get($filepath);

            // Execute SQL
            DB::unprepared($sql);

            Log::info('Database restored from backup', ['filename' => $filename]);

            return true;
        } catch (\Exception $e) {
            Log::error('Backup restore failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get list of all backups
     */
    public function getBackups(): array
    {
        $files = File::files($this->backupPath);

        $backups = [];
        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Sort by created_at descending
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . $filename;

        if (!File::exists($filepath)) {
            throw new \Exception('Backup file not found: ' . $filename);
        }

        File::delete($filepath);
        Log::info('Backup deleted', ['filename' => $filename]);

        return true;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
