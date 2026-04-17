<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $adminSchema = $this->getCurrentSchema();

        $stats = [
            'source_tables' => 0,
            'destination_tables' => 0,
            'admin_tables' => $this->getAdminTableCount(),
            'healthy_connections' => 1,
        ];

        $sourceStatus = [
            'status' => 'pending',
            'database_name' => env('SRC_PG_DATABASE', '-'),
            'schema_name' => env('SRC_PG_SCHEMA', 'public'),
            'tables_count' => 0,
            'message' => 'Source live status not connected in dashboard yet.',
        ];

        $destinationStatus = [
            'status' => 'pending',
            'database_name' => env('DST_PG_DATABASE', env('TGT_PG_DATABASE', '-')),
            'schema_name' => env('DST_PG_SCHEMA', env('TGT_PG_SCHEMA', 'public')),
            'tables_count' => 0,
            'message' => 'Destination live status not connected in dashboard yet.',
        ];

        $adminStatus = [
            'status' => 'connected',
            'database_name' => env('DB_DATABASE', '-'),
            'schema_name' => $adminSchema,
            'tables_count' => $stats['admin_tables'],
            'message' => 'Admin database connected successfully.',
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

    private function getCurrentSchema(): string
    {
        try {
            $row = DB::selectOne('select current_schema() as schema_name');
            return $row->schema_name ?? 'public';
        } catch (\Throwable $e) {
            return 'public';
        }
    }

    private function getAdminTableCount(): int
    {
        try {
            $row = DB::selectOne("
                select count(*) as total
                from information_schema.tables
                where table_schema = current_schema()
                and table_type = 'BASE TABLE'
            ");

            return (int) ($row->total ?? 0);
        } catch (\Throwable $e) {
            return 0;
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