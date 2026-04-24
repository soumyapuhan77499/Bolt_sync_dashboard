<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataCompareRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DataCompareController extends Controller
{
    public function index()
    {
        return $this->renderPage();
    }

    public function run(Request $request)
    {
        $request->validate([
            'table_name' => ['required', 'string', 'max:150'],
            'primary_key_column' => ['nullable', 'string', 'max:150'],
            'selected_columns' => ['nullable', 'string'],
            'row_limit' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $tableName = trim($request->table_name);
        $rowLimit = (int) $request->row_limit;

        $run = DataCompareRun::create([
            'table_name' => $tableName,
            'primary_key_column' => $request->primary_key_column ?: null,
            'compared_columns' => $this->parseCsvText($request->selected_columns),
            'row_limit' => $rowLimit,
            'status' => 'running',
            'message' => 'Comparison started.',
            'started_at' => now(),
            'created_by' => $this->currentAdminId(),
        ]);

        $sourceConfig = $this->resolveSavedConnection('source');
        $destinationConfig = $this->resolveSavedConnection('destination');

        $sourceConnectionName = 'data_compare_source';
        $destinationConnectionName = 'data_compare_destination';

        try {
            $this->registerTempConnection($sourceConnectionName, $sourceConfig);
            $this->registerTempConnection($destinationConnectionName, $destinationConfig);

            if (! $this->tableExists($sourceConnectionName, $sourceConfig['schema_name'], $tableName)) {
                throw new \RuntimeException("Table '{$tableName}' not found in source database.");
            }

            if (! $this->tableExists($destinationConnectionName, $destinationConfig['schema_name'], $tableName)) {
                throw new \RuntimeException("Table '{$tableName}' not found in destination database.");
            }

            $sourceColumns = $this->getTableColumns($sourceConnectionName, $sourceConfig['schema_name'], $tableName);
            $destinationColumns = $this->getTableColumns($destinationConnectionName, $destinationConfig['schema_name'], $tableName);

            $commonColumns = array_values(array_intersect($sourceColumns, $destinationColumns));

            if (empty($commonColumns)) {
                throw new \RuntimeException('No common columns found between source and destination table.');
            }

            $primaryKeyColumn = $this->detectPrimaryKeyColumn(
                $request->primary_key_column,
                $sourceConnectionName,
                $destinationConnectionName,
                $sourceConfig['schema_name'],
                $destinationConfig['schema_name'],
                $tableName,
                $commonColumns
            );

            if (! $primaryKeyColumn) {
                throw new \RuntimeException('Primary key column could not be detected. Please enter it manually.');
            }

            $selectedColumns = $this->parseCsvText($request->selected_columns);

            if (! empty($selectedColumns)) {
                $commonColumns = array_values(array_intersect($commonColumns, $selectedColumns));
            }

            if (! in_array($primaryKeyColumn, $commonColumns, true)) {
                $commonColumns[] = $primaryKeyColumn;
            }

            $commonColumns = array_values(array_unique($commonColumns));

            $sourceTotalRows = DB::connection($sourceConnectionName)->table($tableName)->count();
            $destinationTotalRows = DB::connection($destinationConnectionName)->table($tableName)->count();

            $sourceRows = DB::connection($sourceConnectionName)
                ->table($tableName)
                ->select($commonColumns)
                ->orderBy($primaryKeyColumn)
                ->limit($rowLimit)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

            $destinationRows = DB::connection($destinationConnectionName)
                ->table($tableName)
                ->select($commonColumns)
                ->orderBy($primaryKeyColumn)
                ->limit($rowLimit)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

            $sourceMap = $this->mapRowsByKey($sourceRows, $primaryKeyColumn);
            $destinationMap = $this->mapRowsByKey($destinationRows, $primaryKeyColumn);

            $onlyInSource = [];
            $onlyInDestination = [];
            $changedRows = [];
            $sameRows = [];

            foreach ($sourceMap as $key => $sourceRow) {
                if (! array_key_exists($key, $destinationMap)) {
                    $onlyInSource[] = $sourceRow;
                    continue;
                }

                $destinationRow = $destinationMap[$key];
                $differentColumns = $this->getDifferentColumns($sourceRow, $destinationRow, $primaryKeyColumn);

                if (! empty($differentColumns)) {
                    $changedRows[] = [
                        'key' => $key,
                        'different_columns' => $differentColumns,
                        'source_row' => $sourceRow,
                        'destination_row' => $destinationRow,
                    ];
                } else {
                    $sameRows[] = $sourceRow;
                }
            }

            foreach ($destinationMap as $key => $destinationRow) {
                if (! array_key_exists($key, $sourceMap)) {
                    $onlyInDestination[] = $destinationRow;
                }
            }

            $summary = [
                'table_name' => $tableName,
                'primary_key_column' => $primaryKeyColumn,
                'compared_columns' => $commonColumns,
                'source_total_rows' => $sourceTotalRows,
                'destination_total_rows' => $destinationTotalRows,
                'source_loaded_rows' => count($sourceRows),
                'destination_loaded_rows' => count($destinationRows),
                'only_in_source_count' => count($onlyInSource),
                'only_in_destination_count' => count($onlyInDestination),
                'changed_rows_count' => count($changedRows),
                'same_rows_count' => count($sameRows),
                'row_limit' => $rowLimit,
            ];

            $run->update([
                'primary_key_column' => $primaryKeyColumn,
                'compared_columns' => $commonColumns,
                'source_total_rows' => $sourceTotalRows,
                'destination_total_rows' => $destinationTotalRows,
                'source_loaded_rows' => count($sourceRows),
                'destination_loaded_rows' => count($destinationRows),
                'only_in_source_count' => count($onlyInSource),
                'only_in_destination_count' => count($onlyInDestination),
                'changed_rows_count' => count($changedRows),
                'same_rows_count' => count($sameRows),
                'status' => 'success',
                'message' => 'Data comparison completed successfully.',
                'summary' => $summary,
                'ended_at' => now(),
            ]);

            return $this->renderPage([
                'table_name' => $tableName,
                'primary_key_column' => $primaryKeyColumn,
                'compared_columns' => $commonColumns,
                'source_rows' => $sourceRows,
                'destination_rows' => $destinationRows,
                'only_in_source' => $onlyInSource,
                'only_in_destination' => $onlyInDestination,
                'changed_rows' => $changedRows,
                'same_rows' => $sameRows,
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'ended_at' => now(),
            ]);

            return redirect()
                ->route('admin.data-compare.index')
                ->withInput()
                ->with('error', 'Data compare failed: ' . $e->getMessage());
        } finally {
            DB::disconnect($sourceConnectionName);
            DB::purge($sourceConnectionName);

            DB::disconnect($destinationConnectionName);
            DB::purge($destinationConnectionName);
        }
    }

    private function renderPage(?array $results = null)
    {
        $sourceInfo = $this->getConnectionTables('source');
        $destinationInfo = $this->getConnectionTables('destination');

        $commonTables = array_values(array_intersect(
            $sourceInfo['tables'] ?? [],
            $destinationInfo['tables'] ?? []
        ));

        sort($commonTables);

        $stats = [
            'source_tables' => count($sourceInfo['tables'] ?? []),
            'destination_tables' => count($destinationInfo['tables'] ?? []),
            'common_tables' => count($commonTables),
            'recent_compare_runs' => DataCompareRun::count(),
        ];

        $recentRuns = DataCompareRun::orderByDesc('id')->limit(20)->get();

        return view('admin.data-compare.index', compact(
            'sourceInfo',
            'destinationInfo',
            'commonTables',
            'stats',
            'recentRuns',
            'results'
        ));
    }

    private function getConnectionTables(string $type): array
    {
        $config = $this->resolveSavedConnection($type);

        if (empty($config['host']) || empty($config['database_name']) || empty($config['username'])) {
            return [
                'status' => 'failed',
                'message' => ucfirst($type) . ' connection credentials are incomplete.',
                'tables' => [],
            ];
        }

        $connectionName = 'data_compare_tables_' . $type;

        try {
            $this->registerTempConnection($connectionName, $config);

            $tables = DB::connection($connectionName)->select(
                "select table_name
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = 'BASE TABLE'
                 order by table_name asc",
                [$config['schema_name']]
            );

            return [
                'status' => 'connected',
                'message' => 'Connection successful.',
                'tables' => collect($tables)->pluck('table_name')->toArray(),
                'schema_name' => $config['schema_name'],
                'database_name' => $config['database_name'],
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'tables' => [],
                'schema_name' => $config['schema_name'] ?? 'public',
                'database_name' => $config['database_name'] ?? '-',
            ];
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
        }
    }

    private function tableExists(string $connectionName, string $schemaName, string $tableName): bool
    {
        $row = DB::connection($connectionName)->selectOne(
            "select count(*) as total
             from information_schema.tables
             where table_schema = ?
             and table_name = ?
             and table_type = 'BASE TABLE'",
            [$schemaName, $tableName]
        );

        return ((int) ($row->total ?? 0)) > 0;
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

    private function getPrimaryKeyColumns(string $connectionName, string $schemaName, string $tableName): array
    {
        $rows = DB::connection($connectionName)->select(
            "select kcu.column_name
             from information_schema.table_constraints tc
             join information_schema.key_column_usage kcu
               on tc.constraint_name = kcu.constraint_name
              and tc.table_schema = kcu.table_schema
             where tc.constraint_type = 'PRIMARY KEY'
               and tc.table_schema = ?
               and tc.table_name = ?
             order by kcu.ordinal_position",
            [$schemaName, $tableName]
        );

        return collect($rows)->pluck('column_name')->toArray();
    }

    private function detectPrimaryKeyColumn(
        ?string $requestedPrimaryKey,
        string $sourceConnectionName,
        string $destinationConnectionName,
        string $sourceSchema,
        string $destinationSchema,
        string $tableName,
        array $commonColumns
    ): ?string {
        if (!empty($requestedPrimaryKey)) {
            return trim($requestedPrimaryKey);
        }

        $sourcePk = $this->getPrimaryKeyColumns($sourceConnectionName, $sourceSchema, $tableName);
        $destinationPk = $this->getPrimaryKeyColumns($destinationConnectionName, $destinationSchema, $tableName);

        $commonPk = array_values(array_intersect($sourcePk, $destinationPk));

        if (!empty($commonPk)) {
            return $commonPk[0];
        }

        if (in_array('id', $commonColumns, true)) {
            return 'id';
        }

        return null;
    }

    private function mapRowsByKey(array $rows, string $primaryKeyColumn): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            if (!array_key_exists($primaryKeyColumn, $row)) {
                continue;
            }

            $key = (string) $row[$primaryKeyColumn];
            $mapped[$key] = $row;
        }

        return $mapped;
    }

    private function getDifferentColumns(array $sourceRow, array $destinationRow, string $primaryKeyColumn): array
    {
        $differentColumns = [];

        foreach ($sourceRow as $column => $value) {
            if ($column === $primaryKeyColumn) {
                continue;
            }

            $destinationValue = $destinationRow[$column] ?? null;

            if ((string) $value !== (string) $destinationValue) {
                $differentColumns[] = $column;
            }
        }

        return $differentColumns;
    }

    private function parseCsvText(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    private function currentAdminId(): ?int
    {
        $adminId = session('admin_id');

        if ($adminId === null || $adminId === '') {
            return null;
        }

        return is_numeric($adminId) ? (int) $adminId : null;
    }
}