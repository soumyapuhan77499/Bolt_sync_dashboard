<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReplicationConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReplicationController extends Controller
{
    public function index()
    {
        $currentConfig = ReplicationConfig::latest()->first();
        $recentConfigs = ReplicationConfig::latest()->limit(10)->get();

        $sourceConnection = $this->resolveSavedConnection('source');
        $destinationConnection = $this->resolveSavedConnection('destination');

        $sourceStatus = $this->testResolvedConnection('source', false);
        $destinationStatus = $this->testResolvedConnection('destination', false);

        return view('admin.replication.index', compact(
            'currentConfig',
            'recentConfigs',
            'sourceConnection',
            'destinationConnection',
            'sourceStatus',
            'destinationStatus'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'config_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'publication_name' => ['required', 'string', 'max:150'],
            'subscription_name' => ['required', 'string', 'max:150'],
            'replication_mode' => ['required', 'in:logical,manual'],
            'source_schema_name' => ['required', 'string', 'max:100'],
            'destination_schema_name' => ['required', 'string', 'max:100'],
            'source_tables' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $config = ReplicationConfig::find($validated['config_id'] ?? null);
        if (! $config) {
            $config = new ReplicationConfig();
            $config->status = 'draft';
            $config->created_by = session('admin_user_id');
        }

        $config->name = $validated['name'];
        $config->source_connection_type = 'source';
        $config->destination_connection_type = 'destination';
        $config->publication_name = $validated['publication_name'];
        $config->subscription_name = $validated['subscription_name'];
        $config->replication_mode = $validated['replication_mode'];
        $config->source_schema_name = $validated['source_schema_name'];
        $config->destination_schema_name = $validated['destination_schema_name'];
        $config->source_tables = $this->normalizeCsvText($validated['source_tables'] ?? null);
        $config->sync_inserts = $request->boolean('sync_inserts');
        $config->sync_updates = $request->boolean('sync_updates');
        $config->sync_deletes = $request->boolean('sync_deletes');
        $config->notes = $validated['notes'] ?? null;
        $config->is_active = $request->boolean('is_active', true);
        $config->last_message = 'Replication configuration saved successfully.';
        $config->save();

        return back()->with('success', 'Replication configuration saved successfully.');
    }

    public function start(Request $request)
    {
        $config = $this->findConfig($request);

        if (! $config) {
            return back()->with('error', 'No replication configuration found.');
        }

        $sourceStatus = $this->testResolvedConnection('source', true);
        if ($sourceStatus['status'] !== 'connected') {
            return back()->with('error', 'Source connection failed: ' . $sourceStatus['message']);
        }

        $destinationStatus = $this->testResolvedConnection('destination', true);
        if ($destinationStatus['status'] !== 'connected') {
            return back()->with('error', 'Destination connection failed: ' . $destinationStatus['message']);
        }

        $config->status = 'running';
        $config->is_active = true;
        $config->started_at = now();
        $config->stopped_at = null;
        $config->last_checked_at = now();
        $config->last_message = 'Replication marked as running. Source and destination connections are reachable.';
        $config->save();

        return back()->with('success', 'Replication started successfully.');
    }

    public function stop(Request $request)
    {
        $config = $this->findConfig($request);

        if (! $config) {
            return back()->with('error', 'No replication configuration found.');
        }

        $config->status = 'stopped';
        $config->is_active = false;
        $config->stopped_at = now();
        $config->last_checked_at = now();
        $config->last_message = 'Replication stopped successfully.';
        $config->save();

        return back()->with('success', 'Replication stopped successfully.');
    }

    public function status(Request $request)
    {
        $config = $this->findConfig($request);

        if (! $config) {
            return back()->with('error', 'No replication configuration found.');
        }

        $sourceStatus = $this->testResolvedConnection('source', true);
        $destinationStatus = $this->testResolvedConnection('destination', true);

        $config->last_checked_at = now();
        $config->last_message = sprintf(
            'Source: %s | Destination: %s',
            $sourceStatus['message'] ?? '-',
            $destinationStatus['message'] ?? '-'
        );

        if (
            ($sourceStatus['status'] ?? '') === 'connected' &&
            ($destinationStatus['status'] ?? '') === 'connected' &&
            $config->status === 'running'
        ) {
            $config->status = 'running';
        } elseif ($config->status !== 'stopped') {
            $config->status = 'draft';
        }

        $config->save();

        if ($request->expectsJson()) {
            return response()->json([
                'config' => $config,
                'source' => $sourceStatus,
                'destination' => $destinationStatus,
            ]);
        }

        return back()->with('success', 'Replication status checked successfully.');
    }

    private function findConfig(Request $request): ?ReplicationConfig
    {
        if ($request->filled('config_id')) {
            return ReplicationConfig::find($request->input('config_id'));
        }

        return ReplicationConfig::latest()->first();
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
                'is_active' => true,
                'last_test_status' => null,
                'last_test_message' => null,
                'last_tested_at' => null,
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
                'is_active' => true,
                'last_test_status' => null,
                'last_test_message' => null,
                'last_tested_at' => null,
            ];

        if (! Schema::hasTable('sync_connections')) {
            return $defaults;
        }

        $record = DB::table('sync_connections')
            ->where('connection_type', $type)
            ->first();

        if (! $record) {
            return $defaults;
        }

        return [
            'name' => $record->name ?? $defaults['name'],
            'connection_type' => $record->connection_type ?? $type,
            'host' => $record->host ?? $defaults['host'],
            'port' => $record->port ?? $defaults['port'],
            'database_name' => $record->database_name ?? $defaults['database_name'],
            'username' => $record->username ?? $defaults['username'],
            'password' => !empty($record->password_encrypted)
                ? Crypt::decryptString($record->password_encrypted)
                : $defaults['password'],
            'schema_name' => $record->schema_name ?? $defaults['schema_name'],
            'sslmode' => $record->sslmode ?? $defaults['sslmode'],
            'is_active' => (bool) ($record->is_active ?? true),
            'last_test_status' => $record->last_test_status ?? null,
            'last_test_message' => $record->last_test_message ?? null,
            'last_tested_at' => $record->last_tested_at ?? null,
        ];
    }

    private function testResolvedConnection(string $type, bool $persistResult = false): array
    {
        $record = $this->resolveSavedConnection($type);

        if (empty($record['host']) || empty($record['database_name']) || empty($record['username'])) {
            return [
                'status' => 'failed',
                'message' => ucfirst($type) . ' connection credentials are incomplete.',
                'database_name' => $record['database_name'] ?? '-',
                'schema_name' => $record['schema_name'] ?? 'public',
                'tables_count' => 0,
            ];
        }

        $connectionName = 'temp_replication_' . $type;

        config([
            "database.connections.$connectionName" => [
                'driver' => 'pgsql',
                'host' => $record['host'],
                'port' => $record['port'],
                'database' => $record['database_name'],
                'username' => $record['username'],
                'password' => $record['password'],
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => $record['schema_name'],
                'schema' => $record['schema_name'],
                'sslmode' => $record['sslmode'],
            ],
        ]);

        try {
            DB::purge($connectionName);

            $databaseResult = DB::connection($connectionName)
                ->selectOne('select current_database() as database_name');

            $tableCountResult = DB::connection($connectionName)->selectOne(
                'select count(*) as total from information_schema.tables where table_schema = ? and table_type = ?',
                [$record['schema_name'], 'BASE TABLE']
            );

            $result = [
                'status' => 'connected',
                'message' => sprintf(
                    'Connection successful. DB: %s | Tables in schema "%s": %s',
                    $databaseResult->database_name ?? $record['database_name'],
                    $record['schema_name'],
                    $tableCountResult->total ?? 0
                ),
                'database_name' => $databaseResult->database_name ?? $record['database_name'],
                'schema_name' => $record['schema_name'],
                'tables_count' => (int) ($tableCountResult->total ?? 0),
            ];

            if ($persistResult && Schema::hasTable('sync_connections')) {
                DB::table('sync_connections')
                    ->where('connection_type', $type)
                    ->update([
                        'last_test_status' => 'success',
                        'last_test_message' => $result['message'],
                        'last_tested_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $result;
        } catch (Throwable $e) {
            $result = [
                'status' => 'failed',
                'message' => 'Connection failed: ' . $e->getMessage(),
                'database_name' => $record['database_name'] ?? '-',
                'schema_name' => $record['schema_name'] ?? 'public',
                'tables_count' => 0,
            ];

            if ($persistResult && Schema::hasTable('sync_connections')) {
                DB::table('sync_connections')
                    ->where('connection_type', $type)
                    ->update([
                        'last_test_status' => 'failed',
                        'last_test_message' => $result['message'],
                        'last_tested_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $result;
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
        }
    }

    private function normalizeCsvText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $items = collect(explode(',', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($items) ? null : implode(', ', $items);
    }
}