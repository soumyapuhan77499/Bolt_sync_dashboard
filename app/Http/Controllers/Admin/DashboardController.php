<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $sourceStatus = $this->getExternalDatabaseTables(
            'source_pg',
            env('SRC_PG_HOST'),
            env('SRC_PG_PORT', 5432),
            env('SRC_PG_DATABASE', 'postgres'),
            env('SRC_PG_USERNAME', 'postgres'),
            env('SRC_PG_PASSWORD', ''),
            env('SRC_PG_SCHEMA', 'public'),
            env('SRC_PG_SSLMODE', 'require')
        );

        $destinationStatus = $this->getExternalDatabaseTables(
            'destination_pg',
            env('DST_PG_HOST'),
            env('DST_PG_PORT', 5432),
            env('DST_PG_DATABASE', 'postgres'),
            env('DST_PG_USERNAME', 'postgres'),
            env('DST_PG_PASSWORD', ''),
            env('DST_PG_SCHEMA', 'public'),
            env('DST_PG_SSLMODE', 'disable')
        );

        $adminTables = $this->getAdminTables();

        $adminStatus = [
            'status' => 'connected',
            'database_name' => env('DB_DATABASE', 'postgres'),
            'schema_name'   => env('DB_SCHEMA', 'public'),
            'tables_count'  => count($adminTables),
            'message'       => 'Admin database connected successfully.',
            'tables'        => $adminTables,
        ];

        $stats = [
            'source_tables'      => $sourceStatus['tables_count'] ?? 0,
            'destination_tables' => $destinationStatus['tables_count'] ?? 0,
            'admin_tables'       => count($adminTables),
            'healthy_connections'=> $this->countHealthyConnections($sourceStatus, $destinationStatus, $adminStatus),
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

    private function getExternalDatabaseTables(
        string $connectionName,
        ?string $host,
        $port,
        ?string $database,
        ?string $username,
        ?string $password,
        ?string $schema,
        ?string $sslmode
    ): array {
        try {
            if (empty($host) || empty($database) || empty($username)) {
                return [
                    'status' => 'failed',
                    'database_name' => $database ?: '-',
                    'schema_name' => $schema ?: 'public',
                    'tables_count' => 0,
                    'message' => 'Database credentials are missing.',
                    'tables' => [],
                ];
            }

            config([
                "database.connections.$connectionName" => [
                    'driver' => 'pgsql',
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'search_path' => $schema ?: 'public',
                    'sslmode' => $sslmode ?: 'prefer',
                ]
            ]);

            DB::purge($connectionName);

            $tables = DB::connection($connectionName)->select("
                select table_name
                from information_schema.tables
                where table_schema = ?
                and table_type = 'BASE TABLE'
                order by table_name asc
            ", [$schema ?: 'public']);

            DB::disconnect($connectionName);

            return [
                'status' => 'connected',
                'database_name' => $database,
                'schema_name' => $schema ?: 'public',
                'tables_count' => count($tables),
                'message' => 'Connected successfully.',
                'tables' => collect($tables)->pluck('table_name')->toArray(),
            ];
        } catch (\Throwable $e) {
            DB::disconnect($connectionName);

            return [
                'status' => 'failed',
                'database_name' => $database ?: '-',
                'schema_name' => $schema ?: 'public',
                'tables_count' => 0,
                'message' => $e->getMessage(),
                'tables' => [],
            ];
        }
    }

    private function getAdminTables(): array
    {
        try {
            $schema = env('DB_SCHEMA', 'public');

            $tables = DB::select("
                select table_name
                from information_schema.tables
                where table_schema = ?
                and table_type = 'BASE TABLE'
                order by table_name asc
            ", [$schema]);

            return collect($tables)->pluck('table_name')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function countHealthyConnections(array $sourceStatus, array $destinationStatus, array $adminStatus): int
    {
        $count = 0;

        if (($sourceStatus['status'] ?? '') === 'connected') {
            $count++;
        }

        if (($destinationStatus['status'] ?? '') === 'connected') {
            $count++;
        }

        if (($adminStatus['status'] ?? '') === 'connected') {
            $count++;
        }

        return $count;
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