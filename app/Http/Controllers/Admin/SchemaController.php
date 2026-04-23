<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchemaSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as LaravelSchema;
use Throwable;

class SchemaController extends Controller
{
    public function index(Request $request)
    {
        $fromTarget = $request->input('from_target', 'source');
        $toTarget = $request->input('to_target', 'destination');

        $liveSchemas = [
            'source' => $this->safeCaptureSchema('source'),
            'destination' => $this->safeCaptureSchema('destination'),
            'admin' => $this->safeCaptureSchema('admin'),
        ];

        $diffResult = null;
        if (
            isset($liveSchemas[$fromTarget], $liveSchemas[$toTarget]) &&
            $liveSchemas[$fromTarget]['status'] === 'connected' &&
            $liveSchemas[$toTarget]['status'] === 'connected'
        ) {
            $diffResult = $this->compareSchemas(
                $liveSchemas[$fromTarget]['snapshot'],
                $liveSchemas[$toTarget]['snapshot']
            );
        }

        $recentSnapshots = SchemaSnapshot::latest()->limit(20)->get();

        return view('admin.schema.index', [
            'liveSchemas'      => $liveSchemas,
            'fromTarget'       => $fromTarget,
            'toTarget'         => $toTarget,
            'diffResult'       => $diffResult,
            'recentSnapshots'  => $recentSnapshots,
        ]);
    }

    public function snapshot(Request $request)
    {
        $validated = $request->validate([
            'target_name' => ['required', 'in:source,destination,admin'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->safeCaptureSchema($validated['target_name']);

        if ($result['status'] !== 'connected') {
            return back()->with('error', 'Snapshot failed: ' . $result['message']);
        }

        $snapshot = $result['snapshot'];

        SchemaSnapshot::create([
            'target_name'   => $validated['target_name'],
            'database_name' => $snapshot['database_name'],
            'schema_name'   => $snapshot['schema_name'],
            'snapshot_data' => $snapshot,
            'notes'         => $validated['notes'] ?? null,
            'created_by'    => session('admin_user_id'),
        ]);

        return back()->with('success', ucfirst($validated['target_name']) . ' schema snapshot created successfully.');
    }

    public function diff(Request $request)
    {
        $validated = $request->validate([
            'from_target' => ['required', 'in:source,destination,admin'],
            'to_target'   => ['required', 'in:source,destination,admin'],
        ]);

        return redirect()->route('admin.schema.index', [
            'from_target' => $validated['from_target'],
            'to_target'   => $validated['to_target'],
        ]);
    }

    public function apply(Request $request)
    {
        $validated = $request->validate([
            'from_target' => ['required', 'in:source,admin,destination'],
            'to_target'   => ['required', 'in:destination,admin'],
            'execute'     => ['nullable', 'boolean'],
        ]);

        if ($validated['from_target'] === $validated['to_target']) {
            return back()->with('error', 'Source and destination target cannot be the same.');
        }

        $from = $this->safeCaptureSchema($validated['from_target']);
        $to = $this->safeCaptureSchema($validated['to_target']);

        if ($from['status'] !== 'connected') {
            return back()->with('error', 'Source schema load failed: ' . $from['message']);
        }

        if ($to['status'] !== 'connected') {
            return back()->with('error', 'Destination schema load failed: ' . $to['message']);
        }

        $sqlStatements = $this->buildApplySql(
            $from['snapshot'],
            $to['snapshot']
        );

        if (empty($sqlStatements)) {
            return back()->with('success', 'No schema changes needed.')->with('generated_schema_sql', '-- No changes required');
        }

        if ($request->boolean('execute', true)) {
            $this->executeSqlOnTarget($validated['to_target'], $sqlStatements);

            return back()
                ->with('success', 'Schema changes applied successfully.')
                ->with('generated_schema_sql', implode(";\n\n", $sqlStatements) . ';');
        }

        return back()
            ->with('success', 'Schema SQL generated successfully.')
            ->with('generated_schema_sql', implode(";\n\n", $sqlStatements) . ';');
    }

    private function safeCaptureSchema(string $target): array
    {
        try {
            $snapshot = $this->captureSchema($target);

            return [
                'status'  => 'connected',
                'message' => 'Connected successfully.',
                'snapshot'=> $snapshot,
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => $e->getMessage(),
                'snapshot'=> null,
            ];
        }
    }

    private function captureSchema(string $target): array
    {
        $resolved = $this->resolveConnection($target);
        $connectionName = $resolved['connection_name'];

        if ($resolved['temporary']) {
            config(["database.connections.{$connectionName}" => $resolved['config']]);
            DB::purge($connectionName);
        }

        try {
            $databaseRow = DB::connection($connectionName)
                ->selectOne('select current_database() as database_name');

            $schemaName = $resolved['schema_name'];

            $tables = DB::connection($connectionName)->select(
                "select table_name
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = 'BASE TABLE'
                 order by table_name asc",
                [$schemaName]
            );

            $tableMap = [];

            foreach ($tables as $tableRow) {
                $tableName = $tableRow->table_name;

                $columns = DB::connection($connectionName)->select(
                    "select
                        column_name,
                        data_type,
                        udt_name,
                        is_nullable,
                        column_default,
                        character_maximum_length,
                        numeric_precision,
                        numeric_scale,
                        datetime_precision,
                        ordinal_position
                     from information_schema.columns
                     where table_schema = ?
                     and table_name = ?
                     order by ordinal_position asc",
                    [$schemaName, $tableName]
                );

                $primaryKeys = DB::connection($connectionName)->select(
                    "select kcu.column_name
                     from information_schema.table_constraints tc
                     join information_schema.key_column_usage kcu
                       on tc.constraint_name = kcu.constraint_name
                      and tc.table_schema = kcu.table_schema
                     where tc.table_schema = ?
                       and tc.table_name = ?
                       and tc.constraint_type = 'PRIMARY KEY'
                     order by kcu.ordinal_position asc",
                    [$schemaName, $tableName]
                );

                $columnMap = [];
                foreach ($columns as $column) {
                    $columnMap[$column->column_name] = [
                        'column_name'              => $column->column_name,
                        'data_type'                => $column->data_type,
                        'udt_name'                 => $column->udt_name,
                        'is_nullable'              => $column->is_nullable,
                        'column_default'           => $column->column_default,
                        'character_maximum_length' => $column->character_maximum_length,
                        'numeric_precision'        => $column->numeric_precision,
                        'numeric_scale'            => $column->numeric_scale,
                        'datetime_precision'       => $column->datetime_precision,
                        'ordinal_position'         => $column->ordinal_position,
                    ];
                }

                $tableMap[$tableName] = [
                    'table_name'   => $tableName,
                    'columns'      => $columnMap,
                    'primary_keys' => collect($primaryKeys)->pluck('column_name')->toArray(),
                ];
            }

            return [
                'target_name'    => $target,
                'database_name'  => $databaseRow->database_name ?? '-',
                'schema_name'    => $schemaName,
                'captured_at'    => now()->toDateTimeString(),
                'tables'         => $tableMap,
                'tables_count'   => count($tableMap),
            ];
        } finally {
            if ($resolved['temporary']) {
                DB::disconnect($connectionName);
                DB::purge($connectionName);
            }
        }
    }

    private function resolveConnection(string $target): array
    {
        if ($target === 'admin') {
            return [
                'temporary'      => false,
                'connection_name'=> config('database.default'),
                'schema_name'    => env('DB_SCHEMA', 'public'),
                'config'         => [],
            ];
        }

        if ($target === 'source') {
            return [
                'temporary'      => true,
                'connection_name'=> 'schema_source_pg',
                'schema_name'    => env('SRC_PG_SCHEMA', 'public'),
                'config'         => [
                    'driver' => 'pgsql',
                    'host' => env('SRC_PG_HOST'),
                    'port' => env('SRC_PG_PORT', 5432),
                    'database' => env('SRC_PG_DATABASE', 'postgres'),
                    'username' => env('SRC_PG_USERNAME', 'postgres'),
                    'password' => env('SRC_PG_PASSWORD', ''),
                    'charset' => 'utf8',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'search_path' => env('SRC_PG_SCHEMA', 'public'),
                    'schema' => env('SRC_PG_SCHEMA', 'public'),
                    'sslmode' => env('SRC_PG_SSLMODE', 'require'),
                ],
            ];
        }

        return [
            'temporary'      => true,
            'connection_name'=> 'schema_destination_pg',
            'schema_name'    => env('DST_PG_SCHEMA', 'public'),
            'config'         => [
                'driver' => 'pgsql',
                'host' => env('DST_PG_HOST'),
                'port' => env('DST_PG_PORT', 5432),
                'database' => env('DST_PG_DATABASE', 'postgres'),
                'username' => env('DST_PG_USERNAME', 'postgres'),
                'password' => env('DST_PG_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => env('DST_PG_SCHEMA', 'public'),
                'schema' => env('DST_PG_SCHEMA', 'public'),
                'sslmode' => env('DST_PG_SSLMODE', 'disable'),
            ],
        ];
    }

    private function compareSchemas(array $fromSnapshot, array $toSnapshot): array
    {
        $fromTables = $fromSnapshot['tables'] ?? [];
        $toTables = $toSnapshot['tables'] ?? [];

        $fromTableNames = array_keys($fromTables);
        $toTableNames = array_keys($toTables);

        $missingTablesInTarget = array_values(array_diff($fromTableNames, $toTableNames));
        $extraTablesInTarget = array_values(array_diff($toTableNames, $fromTableNames));
        $commonTables = array_values(array_intersect($fromTableNames, $toTableNames));

        $tableDiffs = [];

        foreach ($commonTables as $tableName) {
            $fromColumns = $fromTables[$tableName]['columns'] ?? [];
            $toColumns = $toTables[$tableName]['columns'] ?? [];

            $fromColumnNames = array_keys($fromColumns);
            $toColumnNames = array_keys($toColumns);

            $missingColumns = array_values(array_diff($fromColumnNames, $toColumnNames));
            $extraColumns = array_values(array_diff($toColumnNames, $fromColumnNames));
            $changedColumns = [];

            foreach (array_intersect($fromColumnNames, $toColumnNames) as $columnName) {
                if (! $this->columnsMatch($fromColumns[$columnName], $toColumns[$columnName])) {
                    $changedColumns[$columnName] = [
                        'source' => $fromColumns[$columnName],
                        'target' => $toColumns[$columnName],
                    ];
                }
            }

            if (! empty($missingColumns) || ! empty($extraColumns) || ! empty($changedColumns)) {
                $tableDiffs[$tableName] = [
                    'missing_columns' => $missingColumns,
                    'extra_columns'   => $extraColumns,
                    'changed_columns' => $changedColumns,
                ];
            }
        }

        return [
            'source_target'            => $fromSnapshot['target_name'] ?? 'source',
            'destination_target'       => $toSnapshot['target_name'] ?? 'destination',
            'missing_tables_in_target' => $missingTablesInTarget,
            'extra_tables_in_target'   => $extraTablesInTarget,
            'table_diffs'              => $tableDiffs,
            'summary'                  => [
                'missing_tables_count' => count($missingTablesInTarget),
                'extra_tables_count'   => count($extraTablesInTarget),
                'changed_tables_count' => count($tableDiffs),
            ],
        ];
    }

    private function columnsMatch(array $source, array $target): bool
    {
        return $this->buildColumnType($source) === $this->buildColumnType($target)
            && ($source['is_nullable'] ?? 'YES') === ($target['is_nullable'] ?? 'YES')
            && trim((string) ($source['column_default'] ?? '')) === trim((string) ($target['column_default'] ?? ''));
    }

    private function buildApplySql(array $fromSnapshot, array $toSnapshot): array
    {
        $sql = [];
        $schemaName = $toSnapshot['schema_name'] ?? 'public';

        $fromTables = $fromSnapshot['tables'] ?? [];
        $toTables = $toSnapshot['tables'] ?? [];

        foreach ($fromTables as $tableName => $tableMeta) {
            if (! array_key_exists($tableName, $toTables)) {
                $sql[] = $this->buildCreateTableSql($schemaName, $tableMeta);
                continue;
            }

            $sourceColumns = $tableMeta['columns'] ?? [];
            $targetColumns = $toTables[$tableName]['columns'] ?? [];

            foreach ($sourceColumns as $columnName => $columnMeta) {
                if (! array_key_exists($columnName, $targetColumns)) {
                    $sql[] = sprintf(
                        'alter table %s.%s add column %s',
                        $this->quoteIdentifier($schemaName),
                        $this->quoteIdentifier($tableName),
                        $this->buildColumnDefinition($columnMeta)
                    );
                }
            }
        }

        return $sql;
    }

    private function buildCreateTableSql(string $schemaName, array $tableMeta): string
    {
        $columnSql = [];
        foreach (($tableMeta['columns'] ?? []) as $column) {
            $columnSql[] = $this->buildColumnDefinition($column);
        }

        $primaryKeys = $tableMeta['primary_keys'] ?? [];
        if (! empty($primaryKeys)) {
            $columnSql[] = 'primary key (' . implode(', ', array_map([$this, 'quoteIdentifier'], $primaryKeys)) . ')';
        }

        return sprintf(
            'create table %s.%s (%s)',
            $this->quoteIdentifier($schemaName),
            $this->quoteIdentifier($tableMeta['table_name']),
            implode(', ', $columnSql)
        );
    }

    private function buildColumnDefinition(array $column): string
    {
        $sql = $this->quoteIdentifier($column['column_name']) . ' ' . $this->buildColumnType($column);

        if (! empty($column['column_default'])) {
            $sql .= ' default ' . $column['column_default'];
        }

        if (($column['is_nullable'] ?? 'YES') === 'NO') {
            $sql .= ' not null';
        }

        return $sql;
    }

    private function buildColumnType(array $column): string
    {
        $dataType = $column['data_type'] ?? 'text';

        return match ($dataType) {
            'character varying', 'varchar' => ! empty($column['character_maximum_length'])
                ? 'varchar(' . $column['character_maximum_length'] . ')'
                : 'varchar',

            'character', 'char' => ! empty($column['character_maximum_length'])
                ? 'char(' . $column['character_maximum_length'] . ')'
                : 'char',

            'numeric', 'decimal' => (! empty($column['numeric_precision']) && $column['numeric_scale'] !== null)
                ? 'numeric(' . $column['numeric_precision'] . ',' . $column['numeric_scale'] . ')'
                : 'numeric',

            'timestamp without time zone' => ! empty($column['datetime_precision'])
                ? 'timestamp(' . $column['datetime_precision'] . ') without time zone'
                : 'timestamp without time zone',

            'timestamp with time zone' => ! empty($column['datetime_precision'])
                ? 'timestamp(' . $column['datetime_precision'] . ') with time zone'
                : 'timestamp with time zone',

            'time without time zone' => ! empty($column['datetime_precision'])
                ? 'time(' . $column['datetime_precision'] . ') without time zone'
                : 'time without time zone',

            'time with time zone' => ! empty($column['datetime_precision'])
                ? 'time(' . $column['datetime_precision'] . ') with time zone'
                : 'time with time zone',

            default => $dataType,
        };
    }

    private function executeSqlOnTarget(string $target, array $sqlStatements): void
    {
        $resolved = $this->resolveConnection($target);
        $connectionName = $resolved['connection_name'];

        if ($resolved['temporary']) {
            config(["database.connections.{$connectionName}" => $resolved['config']]);
            DB::purge($connectionName);
        }

        try {
            DB::connection($connectionName)->beginTransaction();

            foreach ($sqlStatements as $statement) {
                DB::connection($connectionName)->unprepared($statement);
            }

            DB::connection($connectionName)->commit();
        } catch (Throwable $e) {
            DB::connection($connectionName)->rollBack();
            throw $e;
        } finally {
            if ($resolved['temporary']) {
                DB::disconnect($connectionName);
                DB::purge($connectionName);
            }
        }
    }

    private function quoteIdentifier(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
}