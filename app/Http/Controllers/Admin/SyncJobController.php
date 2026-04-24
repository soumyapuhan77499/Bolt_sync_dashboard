<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncRun;
use App\Models\SyncRunLog;
use App\Models\SyncTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncJobController extends Controller
{
    public function index()
    {
        $sourceInfo = $this->getConnectionTables('source');
        $destinationInfo = $this->getConnectionTables('destination');

        $mappings = SyncTable::orderByDesc('id')->get();
        $recentRuns = SyncRun::orderByDesc('id')->limit(20)->get();
        $recentLogs = SyncRunLog::orderByDesc('id')->limit(30)->get();

        $stats = [
            'source_tables' => count($sourceInfo['tables'] ?? []),
            'destination_tables' => count($destinationInfo['tables'] ?? []),
            'active_mappings' => SyncTable::where('is_active', true)->count(),
            'recent_runs' => SyncRun::count(),
        ];

        return view('admin.sync-jobs.index', compact(
            'sourceInfo',
            'destinationInfo',
            'mappings',
            'recentRuns',
            'recentLogs',
            'stats'
        ));
    }

    public function saveMapping(Request $request)
    {
        $validated = $request->validate([
            'mapping_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'transfer_direction' => ['required', 'in:source_to_destination,destination_to_source'],
            'source_table_name' => ['required', 'string', 'max:150'],
            'destination_table_name' => ['required', 'string', 'max:150'],
            'sync_mode' => ['required', 'in:mirror,append'],
            'primary_key_column' => ['nullable', 'string', 'max:150'],
            'selected_columns' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $mapping = SyncTable::find($validated['mapping_id'] ?? null);

        if (! $mapping) {
            $mapping = new SyncTable();
            $mapping->created_by = $this->currentAdminId();
        }

        $mapping->name = $validated['name'];
        $mapping->transfer_direction = $validated['transfer_direction'];
        $mapping->source_table_name = $validated['source_table_name'];
        $mapping->destination_table_name = $validated['destination_table_name'];
        $mapping->sync_mode = $validated['sync_mode'];
        $mapping->primary_key_column = $validated['primary_key_column'] ?? null;
        $mapping->selected_columns = $this->normalizeCsvText($validated['selected_columns'] ?? null);
        $mapping->notes = $validated['notes'] ?? null;
        $mapping->is_active = $request->boolean('is_active', true);
        $mapping->save();

        return back()->with('success', 'Transfer mapping saved successfully.');
    }

    public function run(Request $request)
    {
        $request->validate([
            'mapping_id' => ['required', 'integer'],
        ]);

        $mapping = SyncTable::find($request->mapping_id);

        if (! $mapping) {
            return back()->with('error', 'Transfer mapping not found.');
        }

        $direction = $mapping->transfer_direction ?: 'source_to_destination';

        $readConnectionType = $direction === 'destination_to_source' ? 'destination' : 'source';
        $writeConnectionType = $direction === 'destination_to_source' ? 'source' : 'destination';

        $readTableName = $direction === 'destination_to_source'
            ? $mapping->destination_table_name
            : $mapping->source_table_name;

        $writeTableName = $direction === 'destination_to_source'
            ? $mapping->source_table_name
            : $mapping->destination_table_name;

        $syncRun = SyncRun::create([
            'module_name' => 'manual_sync',
            'run_type' => $direction,
            'sync_table_id' => $mapping->id,
            'source_table_name' => $mapping->source_table_name,
            'destination_table_name' => $mapping->destination_table_name,
            'status' => 'running',
            'records_processed' => 0,
            'started_at' => now(),
            'created_by' => $this->currentAdminId(),
            'message' => 'Manual transfer started.',
        ]);

        $this->createRunLog($syncRun->id, 'info', 'Manual transfer started.', [
            'mapping_id' => $mapping->id,
            'direction' => $direction,
            'read_connection' => $readConnectionType,
            'write_connection' => $writeConnectionType,
            'read_table' => $readTableName,
            'write_table' => $writeTableName,
        ]);

        $readConnection = $this->resolveSavedConnection($readConnectionType);
        $writeConnection = $this->resolveSavedConnection($writeConnectionType);

        $readConnectionName = 'manual_transfer_read';
        $writeConnectionName = 'manual_transfer_write';

        try {
            $this->registerTempConnection($readConnectionName, $readConnection);
            $this->registerTempConnection($writeConnectionName, $writeConnection);

            $readColumns = $this->getTableColumns(
                $readConnectionName,
                $readConnection['schema_name'],
                $readTableName
            );

            $writeColumns = $this->getTableColumns(
                $writeConnectionName,
                $writeConnection['schema_name'],
                $writeTableName
            );

            if (empty($readColumns)) {
                throw new \RuntimeException('Read table columns not found.');
            }

            if (empty($writeColumns)) {
                throw new \RuntimeException('Write table columns not found.');
            }

            $commonColumns = array_values(array_intersect($readColumns, $writeColumns));

            if (! empty($mapping->selected_columns)) {
                $selectedColumns = collect(explode(',', $mapping->selected_columns))
                    ->map(fn ($item) => trim($item))
                    ->filter()
                    ->values()
                    ->all();

                $commonColumns = array_values(array_intersect($commonColumns, $selectedColumns));
            }

            if (empty($commonColumns)) {
                throw new \RuntimeException('No common columns found between selected tables.');
            }

            $rows = DB::connection($readConnectionName)
                ->table($readTableName)
                ->select($commonColumns)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

            DB::connection($writeConnectionName)->beginTransaction();

            if ($mapping->sync_mode === 'mirror') {
                DB::connection($writeConnectionName)
                    ->table($writeTableName)
                    ->delete();
            }

            $inserted = 0;

            foreach (array_chunk($rows, 500) as $chunk) {
                if (! empty($chunk)) {
                    DB::connection($writeConnectionName)
                        ->table($writeTableName)
                        ->insert($chunk);

                    $inserted += count($chunk);
                }
            }

            DB::connection($writeConnectionName)->commit();

            $directionLabel = $direction === 'destination_to_source'
                ? 'Destination to Source'
                : 'Source to Destination';

            $mapping->last_synced_at = now();
            $mapping->last_sync_status = 'success';
            $mapping->last_sync_message = "{$directionLabel} transfer completed successfully. Rows processed: {$inserted}.";
            $mapping->save();

            $syncRun->status = 'success';
            $syncRun->records_processed = $inserted;
            $syncRun->ended_at = now();
            $syncRun->message = "{$directionLabel} transfer completed successfully. Rows processed: {$inserted}.";
            $syncRun->save();

            $this->createRunLog($syncRun->id, 'success', 'Manual transfer completed successfully.', [
                'direction' => $direction,
                'rows_processed' => $inserted,
                'common_columns' => $commonColumns,
                'read_table' => $readTableName,
                'write_table' => $writeTableName,
                'sync_mode' => $mapping->sync_mode,
            ]);

            return back()->with('success', 'Manual transfer completed successfully.');
        } catch (Throwable $e) {
            try {
                DB::connection($writeConnectionName)->rollBack();
            } catch (Throwable $rollbackException) {
            }

            $mapping->last_synced_at = now();
            $mapping->last_sync_status = 'failed';
            $mapping->last_sync_message = $e->getMessage();
            $mapping->save();

            $syncRun->status = 'failed';
            $syncRun->ended_at = now();
            $syncRun->message = $e->getMessage();
            $syncRun->save();

            $this->createRunLog($syncRun->id, 'error', 'Manual transfer failed.', [
                'direction' => $direction,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Manual transfer failed: ' . $e->getMessage());
        } finally {
            DB::disconnect($readConnectionName);
            DB::purge($readConnectionName);

            DB::disconnect($writeConnectionName);
            DB::purge($writeConnectionName);
        }
    }

    private function getConnectionTables(string $type): array
    {
        $record = $this->resolveSavedConnection($type);

        if (empty($record['host']) || empty($record['database_name']) || empty($record['username'])) {
            return [
                'status' => 'failed',
                'message' => ucfirst($type) . ' connection credentials are incomplete.',
                'tables' => [],
            ];
        }

        $connectionName = 'sync_jobs_' . $type;

        try {
            $this->registerTempConnection($connectionName, $record);

            $tables = DB::connection($connectionName)->select(
                "select table_name
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = 'BASE TABLE'
                 order by table_name asc",
                [$record['schema_name']]
            );

            return [
                'status' => 'connected',
                'message' => 'Connection successful.',
                'tables' => collect($tables)->pluck('table_name')->toArray(),
                'schema_name' => $record['schema_name'],
                'database_name' => $record['database_name'],
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'tables' => [],
                'schema_name' => $record['schema_name'] ?? 'public',
                'database_name' => $record['database_name'] ?? '-',
            ];
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
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
        ];
    }

    private function registerTempConnection(string $connectionName, array $record): void
    {
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

        DB::purge($connectionName);
    }

    private function getTableColumns(string $connectionName, string $schemaName, string $tableName): array
    {
        $columns = DB::connection($connectionName)->select(
            "select column_name
             from information_schema.columns
             where table_schema = ?
             and table_name = ?
             order by ordinal_position asc",
            [$schemaName, $tableName]
        );

        return collect($columns)->pluck('column_name')->toArray();
    }

    private function createRunLog(int $runId, string $level, string $message, array $context = []): void
    {
        SyncRunLog::create([
            'sync_run_id' => $runId,
            'level' => $level,
            'message' => $message,
            'context' => empty($context) ? null : $context,
        ]);
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

    private function currentAdminId(): ?int
    {
        $adminId = session('admin_id');

        if ($adminId === null || $adminId === '') {
            return null;
        }

        return is_numeric($adminId) ? (int) $adminId : null;
    }
}