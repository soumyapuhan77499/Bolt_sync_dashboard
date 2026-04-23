<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function index(Request $request)
    {
        $sourceHealth = $this->buildExternalHealth('source', 'Source Database');
        $destinationHealth = $this->buildExternalHealth('destination', 'Destination Database');
        $adminHealth = $this->buildAdminHealth();

        if ($request->boolean('refresh')) {
            $this->storeHealthCheck($sourceHealth);
            $this->storeHealthCheck($destinationHealth);
            $this->storeHealthCheck($adminHealth);

            return redirect()
                ->route('admin.health.index')
                ->with('success', 'Health checks refreshed successfully.');
        }

        $recentHealthChecks = $this->safeGetTableData('health_checks', 'id', 25);

        $stats = [
            'total_checks' => $this->safeCount('health_checks'),
            'successful_checks' => $this->safeCountWhere('health_checks', 'status', 'success')
                + $this->safeCountWhere('health_checks', 'status', 'connected'),
            'failed_checks' => $this->safeCountWhere('health_checks', 'status', 'failed')
                + $this->safeCountWhere('health_checks', 'status', 'error'),
            'live_healthy_targets' => collect([$sourceHealth, $destinationHealth, $adminHealth])
                ->filter(fn ($item) => in_array($item['status'], ['success', 'connected'], true))
                ->count(),
        ];

        return view('admin.health.index', compact(
            'sourceHealth',
            'destinationHealth',
            'adminHealth',
            'recentHealthChecks',
            'stats'
        ));
    }

    private function buildExternalHealth(string $type, string $targetName): array
    {
        try {
            if (!Schema::hasTable('sync_connections')) {
                return [
                    'target_name' => $targetName,
                    'check_type' => 'database_connection',
                    'status' => 'failed',
                    'message' => 'sync_connections table not found.',
                    'checked_at' => now(),
                    'database_name' => '-',
                    'schema_name' => 'public',
                    'tables_count' => 0,
                ];
            }

            $connection = DB::table('sync_connections')
                ->where('connection_type', $type)
                ->where('is_active', true)
                ->first();

            if (!$connection) {
                return [
                    'target_name' => $targetName,
                    'check_type' => 'database_connection',
                    'status' => 'failed',
                    'message' => ucfirst($type) . ' connection not configured.',
                    'checked_at' => now(),
                    'database_name' => '-',
                    'schema_name' => 'public',
                    'tables_count' => 0,
                ];
            }

            $password = '';
            if (!empty($connection->password_encrypted)) {
                $password = Crypt::decryptString($connection->password_encrypted);
            }

            $configName = 'temp_' . $type . '_health';

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
                'target_name' => $targetName,
                'check_type' => 'database_connection',
                'status' => 'success',
                'message' => sprintf(
                    'Connection successful. DB: %s | Tables in schema "%s": %s',
                    $databaseResult->database_name ?? $connection->database_name,
                    $connection->schema_name,
                    $tableCountResult->total ?? 0
                ),
                'checked_at' => now(),
                'database_name' => $databaseResult->database_name ?? $connection->database_name,
                'schema_name' => $connection->schema_name,
                'tables_count' => (int) ($tableCountResult->total ?? 0),
            ];
        } catch (Throwable $e) {
            return [
                'target_name' => $targetName,
                'check_type' => 'database_connection',
                'status' => 'failed',
                'message' => 'Connection failed: ' . $e->getMessage(),
                'checked_at' => now(),
                'database_name' => '-',
                'schema_name' => 'public',
                'tables_count' => 0,
            ];
        }
    }

    private function buildAdminHealth(): array
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
                'target_name' => 'Admin Database',
                'check_type' => 'database_connection',
                'status' => 'success',
                'message' => sprintf(
                    'Connection successful. DB: %s | Tables in schema "%s": %s',
                    $databaseRow->database_name ?? env('DB_DATABASE', '-'),
                    $schemaRow->schema_name ?? env('DB_SCHEMA', 'public'),
                    $tableCountRow->total ?? 0
                ),
                'checked_at' => now(),
                'database_name' => $databaseRow->database_name ?? env('DB_DATABASE', '-'),
                'schema_name' => $schemaRow->schema_name ?? env('DB_SCHEMA', 'public'),
                'tables_count' => (int) ($tableCountRow->total ?? 0),
            ];
        } catch (Throwable $e) {
            return [
                'target_name' => 'Admin Database',
                'check_type' => 'database_connection',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'checked_at' => now(),
                'database_name' => '-',
                'schema_name' => env('DB_SCHEMA', 'public'),
                'tables_count' => 0,
            ];
        }
    }

    private function storeHealthCheck(array $payload): void
    {
        try {
            if (!Schema::hasTable('health_checks')) {
                return;
            }

            $insert = [];

            if (Schema::hasColumn('health_checks', 'target_name')) {
                $insert['target_name'] = $payload['target_name'] ?? null;
            }

            if (Schema::hasColumn('health_checks', 'check_type')) {
                $insert['check_type'] = $payload['check_type'] ?? 'database_connection';
            }

            if (Schema::hasColumn('health_checks', 'status')) {
                $insert['status'] = $payload['status'] ?? 'failed';
            }

            if (Schema::hasColumn('health_checks', 'message')) {
                $insert['message'] = $payload['message'] ?? null;
            }

            if (Schema::hasColumn('health_checks', 'checked_at')) {
                $insert['checked_at'] = $payload['checked_at'] ?? now();
            }

            if (Schema::hasColumn('health_checks', 'created_at')) {
                $insert['created_at'] = now();
            }

            if (Schema::hasColumn('health_checks', 'updated_at')) {
                $insert['updated_at'] = now();
            }

            if (!empty($insert)) {
                DB::table('health_checks')->insert($insert);
            }
        } catch (Throwable $e) {
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