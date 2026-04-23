<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $sourceStatus = $this->getExternalDbStatus('source');
        $destinationStatus = $this->getExternalDbStatus('destination');
        $adminStatus = $this->getAdminDbStatus();

        $recentBackups = $this->safeGetTableData('backup_runs', 'id', 25);

        $stats = [
            'total_backups' => $this->safeCount('backup_runs'),
            'successful_backups' => $this->safeCountWhere('backup_runs', 'status', 'success'),
            'failed_backups' => $this->safeCountWhere('backup_runs', 'status', 'failed'),
            'running_backups' => $this->safeCountWhere('backup_runs', 'status', 'running'),
        ];

        return view('admin.backup.index', compact(
            'sourceStatus',
            'destinationStatus',
            'adminStatus',
            'recentBackups',
            'stats'
        ));
    }

    private function getExternalDbStatus(string $type): array
    {
        try {
            if (!Schema::hasTable('sync_connections')) {
                return [
                    'status' => 'pending',
                    'database_name' => '-',
                    'schema_name' => 'public',
                    'tables_count' => 0,
                    'message' => 'sync_connections table not found.',
                    'tables' => [],
                ];
            }

            $connection = DB::table('sync_connections')
                ->where('connection_type', $type)
                ->where('is_active', true)
                ->first();

            if (!$connection) {
                return [
                    'status' => 'pending',
                    'database_name' => '-',
                    'schema_name' => 'public',
                    'tables_count' => 0,
                    'message' => ucfirst($type) . ' connection not configured.',
                    'tables' => [],
                ];
            }

            $password = '';
            if (!empty($connection->password_encrypted)) {
                $password = Crypt::decryptString($connection->password_encrypted);
            }

            $configName = 'temp_' . $type . '_backup';

            config(["database.connections.$configName" => [
                'driver' => 'pgsql',
                'host' => $connection->host,
                'port' => $connection->port,
                'database' => $connection->database_name,
                'username' => $connection->username,
                'password' => $password,
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => $connection->schema_name,
                'schema' => $connection->schema_name,
                'sslmode' => $connection->sslmode ?: 'disable',
            ]]);

            DB::purge($configName);

            $databaseResult = DB::connection($configName)->selectOne(
                'select current_database() as database_name'
            );

            $tableCountResult = DB::connection($configName)->selectOne(
                'select count(*) as total from information_schema.tables where table_schema = ? and table_type = ?',
                [$connection->schema_name, 'BASE TABLE']
            );

            DB::disconnect($configName);
            DB::purge($configName);

            return [
                'status' => 'connected',
                'database_name' => $databaseResult->database_name ?? $connection->database_name,
                'schema_name' => $connection->schema_name,
                'tables_count' => (int) ($tableCountResult->total ?? 0),
                'message' => ucfirst($type) . ' database connected successfully.',
                'tables' => [],
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'database_name' => '-',
                'schema_name' => 'public',
                'tables_count' => 0,
                'message' => $e->getMessage(),
                'tables' => [],
            ];
        }
    }

    private function getAdminDbStatus(): array
    {
        try {
            $databaseRow = DB::selectOne('select current_database() as database_name');
            $schemaRow = DB::selectOne('select current_schema() as schema_name');
            $tableCountRow = DB::selectOne("
                select count(*) as total
                from information_schema.tables
                where table_schema = current_schema()
                and table_type = 'BASE TABLE'
            ");

            return [
                'status' => 'connected',
                'database_name' => $databaseRow->database_name ?? env('DB_DATABASE', '-'),
                'schema_name' => $schemaRow->schema_name ?? env('DB_SCHEMA', 'public'),
                'tables_count' => (int) ($tableCountRow->total ?? 0),
                'message' => 'Admin database connected successfully.',
                'tables' => [],
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'database_name' => '-',
                'schema_name' => env('DB_SCHEMA', 'public'),
                'tables_count' => 0,
                'message' => $e->getMessage(),
                'tables' => [],
            ];
        }
    }

    private function safeGetTableData(string $table, string $orderBy = 'id', int $limit = 25)
    {
        try {
            if (!Schema::hasTable($table)) {
                return collect();
            }

            return DB::table($table)
                ->orderByDesc($orderBy)
                ->limit($limit)
                ->get();
        } catch (Throwable $e) {
            return collect();
        }
    }

    private function safeCount(string $table): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }

            return DB::table($table)->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function safeCountWhere(string $table, string $column, string $value): int
    {
        try {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return 0;
            }

            return DB::table($table)->where($column, $value)->count();
        } catch (Throwable $e) {
            return 0;
        }
    }
}