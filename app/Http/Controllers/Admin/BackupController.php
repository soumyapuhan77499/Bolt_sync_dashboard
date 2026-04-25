<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupController extends Controller
{
    public function index()
    {
        $stats = $this->getBackupStats();
        $sourceStatus = $this->inspectExternalConnection('source');
        $destinationStatus = $this->inspectExternalConnection('destination');
        $adminStatus = $this->inspectAdminConnection();
        $recentBackups = $this->getRecentBackups();

        return view('admin.backup.index', compact(
            'stats',
            'sourceStatus',
            'destinationStatus',
            'adminStatus',
            'recentBackups'
        ));
    }

    private function getBackupStats(): array
    {
        if (!Schema::hasTable('backup_runs')) {
            return [
                'total_backups' => 0,
                'successful_backups' => 0,
                'failed_backups' => 0,
                'running_backups' => 0,
            ];
        }

        return [
            'total_backups' => BackupRun::count(),
            'successful_backups' => BackupRun::where('status', 'success')->count(),
            'failed_backups' => BackupRun::whereIn('status', ['failed', 'error'])->count(),
            'running_backups' => BackupRun::where('status', 'running')->count(),
        ];
    }

    private function getRecentBackups()
    {
        if (!Schema::hasTable('backup_runs')) {
            return collect();
        }

        return BackupRun::orderByDesc('id')->limit(20)->get();
    }

    private function inspectExternalConnection(string $type): array
    {
        $config = $this->resolveSavedConnection($type);

        if (empty($config['host']) || empty($config['database_name']) || empty($config['username'])) {
            return [
                'status' => 'failed',
                'database_name' => $config['database_name'] ?? '-',
                'schema_name' => $config['schema_name'] ?? 'public',
                'tables_count' => 0,
                'message' => ucfirst($type) . ' connection credentials are incomplete.',
            ];
        }

        if (empty($config['password'])) {
            return [
                'status' => 'failed',
                'database_name' => $config['database_name'] ?? '-',
                'schema_name' => $config['schema_name'] ?? 'public',
                'tables_count' => 0,
                'message' => ucfirst($type) . ' database password is missing. Save the connection again or update the .env password.',
            ];
        }

        $connectionName = 'temp_' . $type . '_backup';

        try {
            $this->registerTempConnection($connectionName, $config);

            $dbRow = DB::connection($connectionName)->selectOne('select current_database() as database_name');
            $schemaRow = DB::connection($connectionName)->selectOne('select current_schema() as schema_name');
            $countRow = DB::connection($connectionName)->selectOne(
                "select count(*) as total
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = 'BASE TABLE'",
                [$config['schema_name']]
            );

            return [
                'status' => 'connected',
                'database_name' => $dbRow->database_name ?? $config['database_name'],
                'schema_name' => $schemaRow->schema_name ?? $config['schema_name'],
                'tables_count' => (int) ($countRow->total ?? 0),
                'message' => ucfirst($type) . ' database connected successfully.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'database_name' => $config['database_name'] ?? '-',
                'schema_name' => $config['schema_name'] ?? 'public',
                'tables_count' => 0,
                'message' => $e->getMessage(),
            ];
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
        }
    }

    private function inspectAdminConnection(): array
    {
        try {
            $dbRow = DB::selectOne('select current_database() as database_name');
            $schemaRow = DB::selectOne('select current_schema() as schema_name');
            $countRow = DB::selectOne(
                "select count(*) as total
                 from information_schema.tables
                 where table_schema = current_schema()
                 and table_type = 'BASE TABLE'"
            );

            return [
                'status' => 'connected',
                'database_name' => $dbRow->database_name ?? env('DB_DATABASE', '-'),
                'schema_name' => $schemaRow->schema_name ?? env('DB_SCHEMA', 'public'),
                'tables_count' => (int) ($countRow->total ?? 0),
                'message' => 'Admin database connected successfully.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'database_name' => env('DB_DATABASE', '-'),
                'schema_name' => env('DB_SCHEMA', 'public'),
                'tables_count' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function resolveSavedConnection(string $type): array
    {
        $defaults = $type === 'source'
            ? [
                'name' => 'Source Supabase',
                'connection_type' => 'source',
                'host' => env('SRC_PG_HOST'),
                'port' => env('SRC_PG_PORT', 5432),
                'database_name' => env('SRC_PG_DATABASE', 'postgres'),
                'username' => env('SRC_PG_USERNAME', 'postgres'),
                'password' => env('SRC_PG_PASSWORD', ''),
                'schema_name' => env('SRC_PG_SCHEMA', 'public'),
                'sslmode' => env('SRC_PG_SSLMODE', 'require'),
            ]
            : [
                'name' => 'Destination Database',
                'connection_type' => 'destination',
                'host' => env('DST_PG_HOST'),
                'port' => env('DST_PG_PORT', 5432),
                'database_name' => env('DST_PG_DATABASE', 'postgres'),
                'username' => env('DST_PG_USERNAME', 'postgres'),
                'password' => env('DST_PG_PASSWORD', ''),
                'schema_name' => env('DST_PG_SCHEMA', 'public'),
                'sslmode' => env('DST_PG_SSLMODE', 'disable'),
            ];

        if (!Schema::hasTable('sync_connections')) {
            return $defaults;
        }

        $record = DB::table('sync_connections')
            ->where('connection_type', $type)
            ->first();

        if (!$record) {
            return $defaults;
        }

        $password = $defaults['password'];

        if (!empty($record->password_encrypted)) {
            try {
                $password = Crypt::decryptString($record->password_encrypted);
            } catch (Throwable $e) {
                $password = $defaults['password'];
            }
        } elseif (isset($record->password) && !empty($record->password)) {
            $password = $record->password;
        }

        return [
            'name' => $record->name ?? $defaults['name'],
            'connection_type' => $record->connection_type ?? $type,
            'host' => $record->host ?? $defaults['host'],
            'port' => $record->port ?? $defaults['port'],
            'database_name' => $record->database_name ?? $defaults['database_name'],
            'username' => $record->username ?? $defaults['username'],
            'password' => $password,
            'schema_name' => $record->schema_name ?? $defaults['schema_name'],
            'sslmode' => $record->sslmode ?? $defaults['sslmode'],
        ];
    }

    private function registerTempConnection(string $connectionName, array $config): void
    {
        config([
            "database.connections.$connectionName" => [
                'driver' => 'pgsql',
                'host' => $config['host'],
                'port' => $config['port'],
                'database' => $config['database_name'],
                'username' => $config['username'],
                'password' => $config['password'],
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => $config['schema_name'],
                'schema' => $config['schema_name'],
                'sslmode' => $config['sslmode'],
            ],
        ]);

        DB::purge($connectionName);
    }
}