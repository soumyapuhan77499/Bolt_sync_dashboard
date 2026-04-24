<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchemaSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SchemaController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderPage();
    }

    public function snapshot(Request $request)
    {
        $request->validate([
            'target_name' => ['required', 'in:source,destination,admin'],
            'notes' => ['nullable', 'string'],
        ]);

        $target = $request->target_name;
        $live = $this->buildLiveSchema($target);

        if (($live['status'] ?? 'failed') !== 'connected') {
            return back()->with('error', 'Snapshot failed: ' . ($live['message'] ?? 'Unable to read schema.'));
        }

        $snapshot = $live['snapshot'];

        SchemaSnapshot::create([
            'target_name' => $target,
            'database_name' => $snapshot['database_name'] ?? '-',
            'schema_name' => $snapshot['schema_name'] ?? 'public',
            'snapshot_data' => $snapshot,
            'notes' => $request->notes,
            'created_by' => session('admin_user_id'),
        ]);

        return back()->with('success', ucfirst($target) . ' snapshot created successfully.');
    }

    public function diff(Request $request)
    {
        $request->validate([
            'from_target' => ['required', 'in:source,destination,admin'],
            'to_target' => ['required', 'in:source,destination,admin'],
        ]);

        $fromTarget = $request->from_target;
        $toTarget = $request->to_target;

        $liveSchemas = [
            'source' => $this->buildLiveSchema('source'),
            'destination' => $this->buildLiveSchema('destination'),
            'admin' => $this->buildLiveSchema('admin'),
        ];

        if (($liveSchemas[$fromTarget]['status'] ?? 'failed') !== 'connected') {
            return back()->with('error', ucfirst($fromTarget) . ' schema connection failed.');
        }

        if (($liveSchemas[$toTarget]['status'] ?? 'failed') !== 'connected') {
            return back()->with('error', ucfirst($toTarget) . ' schema connection failed.');
        }

        $diffResult = $this->compareSchemas(
            $liveSchemas[$fromTarget]['snapshot'],
            $liveSchemas[$toTarget]['snapshot'],
            $fromTarget,
            $toTarget
        );

        return $this->renderPage($diffResult, $fromTarget, $toTarget, $liveSchemas);
    }

    public function apply(Request $request)
    {
        $request->validate([
            'from_target' => ['required', 'in:source,destination,admin'],
            'to_target' => ['required', 'in:source,destination,admin'],
        ]);

        $fromTarget = $request->from_target;
        $toTarget = $request->to_target;

        $fromLive = $this->buildLiveSchema($fromTarget);
        $toLive = $this->buildLiveSchema($toTarget);

        if (($fromLive['status'] ?? 'failed') !== 'connected') {
            return back()->with('error', ucfirst($fromTarget) . ' schema connection failed.');
        }

        if (($toLive['status'] ?? 'failed') !== 'connected') {
            return back()->with('error', ucfirst($toTarget) . ' schema connection failed.');
        }

        $diffResult = $this->compareSchemas(
            $fromLive['snapshot'],
            $toLive['snapshot'],
            $fromTarget,
            $toTarget
        );

        [$statements, $sqlText] = $this->generateSchemaStatements(
            $fromLive['snapshot'],
            $toLive['snapshot'],
            $diffResult
        );

        if (empty($statements)) {
            return back()
                ->with('success', 'No missing tables or columns found to apply.')
                ->with('generated_schema_sql', '-- No schema changes required.');
        }

        $targetConnection = $this->resolveConnectionConfig($toTarget);
        $connectionName = 'schema_apply_target';

        try {
            $this->registerTempConnection($connectionName, $targetConnection);

            DB::connection($connectionName)->beginTransaction();

            foreach ($statements as $statement) {
                DB::connection($connectionName)->statement($statement);
            }

            DB::connection($connectionName)->commit();

            return back()
                ->with('success', 'Missing tables/columns applied successfully.')
                ->with('generated_schema_sql', $sqlText);
        } catch (Throwable $e) {
            try {
                DB::connection($connectionName)->rollBack();
            } catch (Throwable $rollbackException) {
            }

            return back()
                ->with('error', 'Schema apply failed: ' . $e->getMessage())
                ->with('generated_schema_sql', $sqlText);
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
        }
    }

    private function renderPage(
        ?array $diffResult = null,
        string $fromTarget = 'source',
        string $toTarget = 'destination',
        ?array $liveSchemas = null
    ) {
        $liveSchemas = $liveSchemas ?: [
            'source' => $this->buildLiveSchema('source'),
            'destination' => $this->buildLiveSchema('destination'),
            'admin' => $this->buildLiveSchema('admin'),
        ];

        $recentSnapshots = SchemaSnapshot::orderByDesc('id')->limit(20)->get();

        return view('admin.schema.index', compact(
            'liveSchemas',
            'recentSnapshots',
            'diffResult',
            'fromTarget',
            'toTarget'
        ));
    }

    private function buildLiveSchema(string $target): array
    {
        $config = $this->resolveConnectionConfig($target);

        if (empty($config['host']) || empty($config['database_name']) || empty($config['username'])) {
            return [
                'status' => 'failed',
                'message' => ucfirst($target) . ' connection is incomplete.',
                'snapshot' => [
                    'target_name' => $target,
                    'database_name' => '-',
                    'schema_name' => $config['schema_name'] ?? 'public',
                    'tables_count' => 0,
                    'columns_count' => 0,
                    'tables' => [],
                ],
            ];
        }

        $connectionName = 'schema_live_' . $target . '_' . uniqid();

        try {
            $this->registerTempConnection($connectionName, $config);

            $databaseRow = DB::connection($connectionName)->selectOne(
                'select current_database() as database_name'
            );

            $tableRows = DB::connection($connectionName)->select(
                "select table_name
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = 'BASE TABLE'
                 order by table_name asc",
                [$config['schema_name']]
            );

            $columnRows = DB::connection($connectionName)->select(
                "select
                    table_name,
                    column_name,
                    data_type,
                    udt_name,
                    character_maximum_length,
                    numeric_precision,
                    numeric_scale,
                    datetime_precision,
                    is_nullable,
                    column_default,
                    ordinal_position
                 from information_schema.columns
                 where table_schema = ?
                 order by table_name asc, ordinal_position asc",
                [$config['schema_name']]
            );

            $primaryKeyRows = DB::connection($connectionName)->select(
                "select
                    kcu.table_name,
                    kcu.column_name
                 from information_schema.table_constraints tc
                 join information_schema.key_column_usage kcu
                   on tc.constraint_name = kcu.constraint_name
                  and tc.table_schema = kcu.table_schema
                 where tc.constraint_type = 'PRIMARY KEY'
                   and tc.table_schema = ?
                 order by kcu.table_name asc, kcu.ordinal_position asc",
                [$config['schema_name']]
            );

            $primaryMap = [];
            foreach ($primaryKeyRows as $pk) {
                $primaryMap[$pk->table_name][] = $pk->column_name;
            }

            $columnsByTable = [];
            foreach ($columnRows as $column) {
                $columnsByTable[$column->table_name][] = [
                    'column_name' => $column->column_name,
                    'data_type' => $column->data_type,
                    'udt_name' => $column->udt_name,
                    'character_maximum_length' => $column->character_maximum_length,
                    'numeric_precision' => $column->numeric_precision,
                    'numeric_scale' => $column->numeric_scale,
                    'datetime_precision' => $column->datetime_precision,
                    'is_nullable' => $column->is_nullable,
                    'column_default' => $column->column_default,
                    'ordinal_position' => $column->ordinal_position,
                    'is_primary' => in_array($column->column_name, $primaryMap[$column->table_name] ?? [], true),
                ];
            }

            $tables = [];
            $columnsCount = 0;

            foreach ($tableRows as $tableRow) {
                $tableName = $tableRow->table_name;
                $tableColumns = $columnsByTable[$tableName] ?? [];
                $columnsCount += count($tableColumns);

                $tables[] = [
                    'table_name' => $tableName,
                    'columns_count' => count($tableColumns),
                    'columns' => $tableColumns,
                ];
            }

            return [
                'status' => 'connected',
                'message' => 'Live schema loaded successfully.',
                'snapshot' => [
                    'target_name' => $target,
                    'database_name' => $databaseRow->database_name ?? $config['database_name'],
                    'schema_name' => $config['schema_name'],
                    'tables_count' => count($tables),
                    'columns_count' => $columnsCount,
                    'tables' => $tables,
                ],
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'snapshot' => [
                    'target_name' => $target,
                    'database_name' => '-',
                    'schema_name' => $config['schema_name'] ?? 'public',
                    'tables_count' => 0,
                    'columns_count' => 0,
                    'tables' => [],
                ],
            ];
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
        }
    }

    private function compareSchemas(array $fromSnapshot, array $toSnapshot, string $fromTarget, string $toTarget): array
    {
        $fromTables = collect($fromSnapshot['tables'] ?? [])->keyBy('table_name');
        $toTables = collect($toSnapshot['tables'] ?? [])->keyBy('table_name');

        $fromTableNames = $fromTables->keys()->all();
        $toTableNames = $toTables->keys()->all();

        $missingTablesInTarget = array_values(array_diff($fromTableNames, $toTableNames));
        $extraTablesInTarget = array_values(array_diff($toTableNames, $fromTableNames));

        $tableDiffs = [];

        foreach (array_intersect($fromTableNames, $toTableNames) as $tableName) {
            $fromColumns = collect($fromTables[$tableName]['columns'] ?? [])->keyBy('column_name');
            $toColumns = collect($toTables[$tableName]['columns'] ?? [])->keyBy('column_name');

            $fromColumnNames = $fromColumns->keys()->all();
            $toColumnNames = $toColumns->keys()->all();

            $missingColumns = array_values(array_diff($fromColumnNames, $toColumnNames));
            $extraColumns = array_values(array_diff($toColumnNames, $fromColumnNames));

            $changedColumns = [];

            foreach (array_intersect($fromColumnNames, $toColumnNames) as $columnName) {
                $fromCol = $fromColumns[$columnName];
                $toCol = $toColumns[$columnName];

                if ($this->columnSignature($fromCol) !== $this->columnSignature($toCol)) {
                    $changedColumns[$columnName] = [
                        'source' => $fromCol,
                        'target' => $toCol,
                    ];
                }
            }

            if (!empty($missingColumns) || !empty($extraColumns) || !empty($changedColumns)) {
                $tableDiffs[$tableName] = [
                    'missing_columns' => $missingColumns,
                    'extra_columns' => $extraColumns,
                    'changed_columns' => $changedColumns,
                ];
            }
        }

        return [
            'source_target' => $fromTarget,
            'destination_target' => $toTarget,
            'missing_tables_in_target' => $missingTablesInTarget,
            'extra_tables_in_target' => $extraTablesInTarget,
            'table_diffs' => $tableDiffs,
            'summary' => [
                'missing_tables_count' => count($missingTablesInTarget),
                'extra_tables_count' => count($extraTablesInTarget),
                'changed_tables_count' => count($tableDiffs),
            ],
        ];
    }

    private function columnSignature(array $column): string
    {
        return json_encode([
            'data_type' => $column['data_type'] ?? null,
            'udt_name' => $column['udt_name'] ?? null,
            'character_maximum_length' => $column['character_maximum_length'] ?? null,
            'numeric_precision' => $column['numeric_precision'] ?? null,
            'numeric_scale' => $column['numeric_scale'] ?? null,
            'datetime_precision' => $column['datetime_precision'] ?? null,
            'is_nullable' => $column['is_nullable'] ?? null,
            'column_default' => $column['column_default'] ?? null,
            'is_primary' => $column['is_primary'] ?? false,
        ]);
    }

    private function generateSchemaStatements(array $fromSnapshot, array $toSnapshot, array $diffResult): array
    {
        $fromTables = collect($fromSnapshot['tables'] ?? [])->keyBy('table_name');

        $statements = [];

        foreach ($diffResult['missing_tables_in_target'] ?? [] as $tableName) {
            $table = $fromTables[$tableName] ?? null;
            if (!$table) {
                continue;
            }

            $columnSql = collect($table['columns'] ?? [])
                ->map(fn ($column) => $this->buildColumnSql($column, true))
                ->implode(",\n    ");

            if (!empty($columnSql)) {
                $statements[] = 'create table "' . $tableName . "\" (\n    " . $columnSql . "\n)";
            }
        }

        foreach (($diffResult['table_diffs'] ?? []) as $tableName => $meta) {
            $fromColumns = collect(($fromTables[$tableName]['columns'] ?? []))->keyBy('column_name');

            foreach (($meta['missing_columns'] ?? []) as $columnName) {
                $column = $fromColumns[$columnName] ?? null;
                if (!$column) {
                    continue;
                }

                $statements[] = 'alter table "' . $tableName . '" add column ' . $this->buildColumnSql($column, false);
            }
        }

        $sqlText = empty($statements)
            ? '-- No missing tables or columns found.'
            : implode(";\n\n", $statements) . ';';

        if (!empty($diffResult['table_diffs'])) {
            $sqlText .= "\n\n-- Changed columns below need manual review.\n";
            foreach ($diffResult['table_diffs'] as $tableName => $meta) {
                foreach (($meta['changed_columns'] ?? []) as $columnName => $columnDiff) {
                    $sqlText .= '-- ' . $tableName . '.' . $columnName . ' differs between source and target.' . "\n";
                }
            }
        }

        return [$statements, $sqlText];
    }

    private function buildColumnSql(array $column, bool $includePrimaryKey = true): string
    {
        $parts = [];
        $parts[] = '"' . $column['column_name'] . '"';
        $parts[] = $this->mapColumnType($column);

        if (!empty($column['column_default'])) {
            $parts[] = 'default ' . $column['column_default'];
        }

        if (($column['is_nullable'] ?? 'YES') === 'NO') {
            $parts[] = 'not null';
        }

        if ($includePrimaryKey && !empty($column['is_primary'])) {
            $parts[] = 'primary key';
        }

        return implode(' ', $parts);
    }

    private function mapColumnType(array $column): string
    {
        $type = strtolower((string) ($column['data_type'] ?? 'text'));
        $udtName = strtolower((string) ($column['udt_name'] ?? ''));
        $length = $column['character_maximum_length'] ?? null;
        $precision = $column['numeric_precision'] ?? null;
        $scale = $column['numeric_scale'] ?? null;

        return match ($type) {
            'character varying' => $length ? "varchar({$length})" : 'varchar',
            'character' => $length ? "char({$length})" : 'char',
            'timestamp without time zone' => 'timestamp',
            'timestamp with time zone' => 'timestamptz',
            'time without time zone' => 'time',
            'time with time zone' => 'timetz',
            'numeric' => ($precision && $scale !== null) ? "numeric({$precision},{$scale})" : 'numeric',
            'array' => ltrim($udtName, '_') . '[]',
            'USER-DEFINED' => $udtName ?: 'text',
            default => $type ?: ($udtName ?: 'text'),
        };
    }

    private function resolveConnectionConfig(string $target): array
    {
        if ($target === 'admin') {
            return [
                'name' => 'Admin Database',
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT', 5432),
                'database_name' => env('DB_DATABASE', 'postgres'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', ''),
                'schema_name' => env('DB_SCHEMA', 'public'),
                'sslmode' => env('DB_SSLMODE', 'disable'),
            ];
        }

        $defaults = $target === 'source'
            ? [
                'name' => 'Source Database',
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
            ->where('connection_type', $target)
            ->first();

        if (!$record) {
            return $defaults;
        }

        return [
            'name' => $record->name ?? $defaults['name'],
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
}