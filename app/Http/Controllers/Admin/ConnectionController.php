<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConnectionController extends Controller
{
    public function index()
    {
        $savedConnections = [
            'source' => null,
            'destination' => null,
        ];

        if (Schema::hasTable('sync_connections')) {
            $records = DB::table('sync_connections')
                ->whereIn('connection_type', ['source', 'destination'])
                ->get()
                ->keyBy('connection_type');

            $savedConnections['source'] = $records->get('source');
            $savedConnections['destination'] = $records->get('destination');
        }

        $sourceConnection = [
            'name' => $savedConnections['source']->name ?? 'Source Supabase',
            'connection_type' => 'source',
            'host' => $savedConnections['source']->host ?? env('SRC_PG_HOST'),
            'port' => $savedConnections['source']->port ?? env('SRC_PG_PORT', 5432),
            'database_name' => $savedConnections['source']->database_name ?? env('SRC_PG_DATABASE', 'postgres'),
            'username' => $savedConnections['source']->username ?? env('SRC_PG_USERNAME', 'postgres'),
            'schema_name' => $savedConnections['source']->schema_name ?? env('SRC_PG_SCHEMA', 'public'),
            'sslmode' => $savedConnections['source']->sslmode ?? env('SRC_PG_SSLMODE', 'require'),
            'is_active' => (bool) ($savedConnections['source']->is_active ?? true),
            'last_test_status' => $savedConnections['source']->last_test_status ?? null,
            'last_test_message' => $savedConnections['source']->last_test_message ?? null,
            'last_tested_at' => $savedConnections['source']->last_tested_at ?? null,
        ];

        $destinationConnection = [
            'name' => $savedConnections['destination']->name ?? 'Destination Database',
            'connection_type' => 'destination',
            'host' => $savedConnections['destination']->host ?? env('DST_PG_HOST'),
            'port' => $savedConnections['destination']->port ?? env('DST_PG_PORT', 5432),
            'database_name' => $savedConnections['destination']->database_name ?? env('DST_PG_DATABASE', 'postgres'),
            'username' => $savedConnections['destination']->username ?? env('DST_PG_USERNAME', 'postgres'),
            'schema_name' => $savedConnections['destination']->schema_name ?? env('DST_PG_SCHEMA', 'public'),
            'sslmode' => $savedConnections['destination']->sslmode ?? env('DST_PG_SSLMODE', 'disable'),
            'is_active' => (bool) ($savedConnections['destination']->is_active ?? true),
            'last_test_status' => $savedConnections['destination']->last_test_status ?? null,
            'last_test_message' => $savedConnections['destination']->last_test_message ?? null,
            'last_tested_at' => $savedConnections['destination']->last_tested_at ?? null,
        ];

        return view('admin.connections.index', compact('sourceConnection', 'destinationConnection'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'connection_type' => ['required', 'in:source,destination'],
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'numeric'],
            'database_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'schema_name' => ['required', 'string', 'max:100'],
            'sslmode' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! Schema::hasTable('sync_connections')) {
            return back()->with('error', 'sync_connections table not found. Please run migrations first.');
        }

        $payload = [
            'name' => $validated['name'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'database_name' => $validated['database_name'],
            'username' => $validated['username'],
            'schema_name' => $validated['schema_name'],
            'sslmode' => $validated['sslmode'],
            'is_active' => $request->boolean('is_active'),
            'updated_at' => now(),
        ];

        if (! empty($validated['password'])) {
            $payload['password_encrypted'] = Crypt::encryptString($validated['password']);
        }

        DB::table('sync_connections')->updateOrInsert(
            ['connection_type' => $validated['connection_type']],
            array_merge($payload, ['created_at' => now()])
        );

        return back()->with('success', ucfirst($validated['connection_type']) . ' connection saved successfully.');
    }

    public function testSource(Request $request)
    {
        return $this->testConnection($request, 'source');
    }

    public function testDestination(Request $request)
    {
        return $this->testConnection($request, 'destination');
    }

    private function testConnection(Request $request, string $type)
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'numeric'],
            'database_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'schema_name' => ['nullable', 'string', 'max:100'],
            'sslmode' => ['nullable', 'string', 'max:50'],
        ]);

        $config = $this->buildTestConnectionConfig($validated, $type);

        config(['database.connections.temp_sync_test' => $config]);

        try {
            DB::purge('temp_sync_test');

            $databaseResult = DB::connection('temp_sync_test')->selectOne('select current_database() as database_name');
            $versionResult = DB::connection('temp_sync_test')->selectOne('select version() as version');
            $tableCountResult = DB::connection('temp_sync_test')->selectOne(
                'select count(*) as total from information_schema.tables where table_schema = ? and table_type = ?',
                [$config['schema'], 'BASE TABLE']
            );

            $message = sprintf(
                'Connection successful. DB: %s | Tables in schema "%s": %s',
                $databaseResult->database_name ?? '-',
                $config['schema'],
                $tableCountResult->total ?? 0
            );

            $this->updateSavedTestStatus($type, 'success', $message);

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            $message = 'Connection failed: ' . $e->getMessage();

            $this->updateSavedTestStatus($type, 'failed', $message);

            return back()->with('error', $message);
        } finally {
            DB::disconnect('temp_sync_test');
            DB::purge('temp_sync_test');
        }
    }

    private function buildTestConnectionConfig(array $validated, string $type): array
    {
        if ($type === 'source') {
            $host = $validated['host'] ?? env('SRC_PG_HOST', '127.0.0.1');
            $port = $validated['port'] ?? env('SRC_PG_PORT', 5432);
            $database = $validated['database_name'] ?? env('SRC_PG_DATABASE', 'postgres');
            $username = $validated['username'] ?? env('SRC_PG_USERNAME', 'postgres');
            $password = $validated['password'] ?? env('SRC_PG_PASSWORD', '');
            $schema = $validated['schema_name'] ?? env('SRC_PG_SCHEMA', 'public');
            $sslmode = $validated['sslmode'] ?? env('SRC_PG_SSLMODE', 'require');
        } else {
            $host = $validated['host'] ?? env('DST_PG_HOST', '127.0.0.1');
            $port = $validated['port'] ?? env('DST_PG_PORT', 5432);
            $database = $validated['database_name'] ?? env('DST_PG_DATABASE', 'postgres');
            $username = $validated['username'] ?? env('DST_PG_USERNAME', 'postgres');
            $password = $validated['password'] ?? env('DST_PG_PASSWORD', '');
            $schema = $validated['schema_name'] ?? env('DST_PG_SCHEMA', 'public');
            $sslmode = $validated['sslmode'] ?? env('DST_PG_SSLMODE', 'disable');
        }

        return [
            'driver' => 'pgsql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => $schema,
            'schema' => $schema,
            'sslmode' => $sslmode,
        ];
    }

    private function updateSavedTestStatus(string $type, string $status, string $message): void
    {
        if (! Schema::hasTable('sync_connections')) {
            return;
        }

        DB::table('sync_connections')
            ->where('connection_type', $type)
            ->update([
                'last_test_status' => $status,
                'last_test_message' => $message,
                'last_tested_at' => now(),
                'updated_at' => now(),
            ]);
    }
}