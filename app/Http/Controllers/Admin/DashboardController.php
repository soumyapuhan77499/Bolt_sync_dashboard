<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $adminSchema = $this->getCurrentSchema();

        $sourceStatus = $this->getExternalDbStatus('source');
        $destinationStatus = $this->getExternalDbStatus('destination');

        $adminTables = $this->getAdminTableNames();

        $adminStatus = [
            'status' => 'connected',
            'database_name' => env('DB_DATABASE', '-'),
            'schema_name' => $adminSchema,
            'tables_count' => count($adminTables),
            'message' => 'Admin database connected successfully.',
            'tables' => $adminTables,
        ];

        $stats = [
            'source_tables' => $sourceStatus['tables_count'] ?? 0,
            'destination_tables' => $destinationStatus['tables_count'] ?? 0,
            'admin_tables' => count($adminTables),
            'healthy_connections' => (($sourceStatus['status'] ?? '') === 'connected' ? 1 : 0)
                + (($destinationStatus['status'] ?? '') === 'connected' ? 1 : 0)
                + 1,
        ];

        $recentSyncRuns = $this->safeGetTableData('sync_runs', 'id', 10);
        $recentHealthChecks = $this->safeGetTableData('health_checks', 'id', 10);
        $recentBackups = $this->safeGetTableData('backup_runs', 'id', 10);
        $recentAuditLogs = $this->safeGetTableData('audit_logs', 'id', 10);

        return view('admin.dashboard.index', compact(
            'stats',
            'sourceStatus',
            'destinationStatus',
            'adminStatus',
            'recentSyncRuns',
            'recentHealthChecks',
            'recentBackups',
            'recentAuditLogs'
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

            $configName = 'temp_' . $type . '_dashboard';

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

            $databaseResult = DB::connection($configName)->selectOne('select current_database() as database_name');

            $tables = DB::connection($configName)->select(
                "select table_name
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = 'BASE TABLE'
                 order by table_name asc",
                [$connection->schema_name]
            );

            DB::disconnect($configName);
            DB::purge($configName);

            return [
                'status' => 'connected',
                'database_name' => $databaseResult->database_name ?? $connection->database_name,
                'schema_name' => $connection->schema_name,
                'tables_count' => count($tables),
                'message' => ucfirst($type) . ' database connected successfully.',
                'tables' => collect($tables)->pluck('table_name')->toArray(),
            ];
        } catch (\Throwable $e) {
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

    private function getCurrentSchema(): string
    {
        try {
            $row = DB::selectOne('select current_schema() as schema_name');
            return $row->schema_name ?? 'public';
        } catch (\Throwable $e) {
            return 'public';
        }
    }

    private function getAdminTableNames(): array
    {
        try {
            $tables = DB::select("
                select table_name
                from information_schema.tables
                where table_schema = current_schema()
                and table_type = 'BASE TABLE'
                order by table_name asc
            ");

            return collect($tables)->pluck('table_name')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function safeGetTableData(string $table, string $orderBy = 'id', int $limit = 10)
    {
        try {
            if (!Schema::hasTable($table)) {
                return collect();
            }

            return DB::table($table)
                ->orderByDesc($orderBy)
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}