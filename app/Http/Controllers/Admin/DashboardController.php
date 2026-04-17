<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $sourceStatus = $this->getConnectionSummary('source_pg', 'Source Supabase');
        $destinationStatus = $this->getConnectionSummary('destination_pg', 'Destination Database');
        $adminStatus = $this->getConnectionSummary(config('database.default'), 'Admin Database');

        $stats = [
            'source_tables' => $sourceStatus['tables_count'],
            'destination_tables' => $destinationStatus['tables_count'],
            'admin_tables' => $adminStatus['tables_count'],
            'healthy_connections' => collect([$sourceStatus, $destinationStatus, $adminStatus])
                ->where('status', 'connected')
                ->count(),
        ];

        $recentSyncRuns = $this->getRecentTableData('sync_runs');
        $recentHealthChecks = $this->getRecentTableData('health_checks');
        $recentBackups = $this->getRecentTableData('backup_runs');
        $recentAuditLogs = $this->getRecentTableData('audit_logs');

        return view('admin.dashboard.index', compact(
            'sourceStatus',
            'destinationStatus',
            'adminStatus',
            'stats',
            'recentSyncRuns',
            'recentHealthChecks',
            'recentBackups',
            'recentAuditLogs'
        ));
    }

    private function getConnectionSummary(string $connectionName, string $label): array
    {
        try {
            $dbNameResult = DB::connection($connectionName)->selectOne('select current_database() as database_name');
            $versionResult = DB::connection($connectionName)->selectOne('select version() as version');

            $schemaName = $this->getSchemaName($connectionName);

            $tableCountResult = DB::connection($connectionName)->selectOne(
                'select count(*) as total from information_schema.tables where table_schema = ? and table_type = ?',
                [$schemaName, 'BASE TABLE']
            );

            return [
                'label' => $label,
                'connection' => $connectionName,
                'status' => 'connected',
                'database_name' => $dbNameResult->database_name ?? '-',
                'version' => $versionResult->version ?? '-',
                'schema_name' => $schemaName,
                'tables_count' => (int) ($tableCountResult->total ?? 0),
                'message' => 'Connection successful',
            ];
        } catch (\Throwable $e) {
            return [
                'label' => $label,
                'connection' => $connectionName,
                'status' => 'failed',
                'database_name' => '-',
                'version' => '-',
                'schema_name' => $this->getSchemaName($connectionName),
                'tables_count' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getSchemaName(string $connectionName): string
    {
        $connection = config("database.connections.{$connectionName}", []);

        return $connection['schema']
            ?? $connection['search_path']
            ?? 'public';
    }

    private function getRecentTableData(string $table): array
    {
        try {
            if (! Schema::hasTable($table)) {
                return [];
            }

            return DB::table($table)
                ->latest('id')
                ->limit(5)
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}