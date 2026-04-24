@extends('admin.layouts.app')

@section('title', 'Data Compare - Bolt Sync Admin')
@section('page_title', 'Data Compare')
@section('page_subtitle', 'Compare source and destination table rows with difference view')

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }

    .card-box,
    .form-card,
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .card-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .card-value {
        font-size: 30px;
        font-weight: 800;
        color: #111827;
        line-height: 1;
    }

    .two-col-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .form-card h3,
    .table-card h3 {
        font-size: 18px;
        margin-bottom: 16px;
        color: #111827;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
    }

    .form-note {
        font-size: 12px;
        color: #6b7280;
    }

    .form-control,
    .form-select {
        width: 100%;
        min-height: 46px;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        background: #fff;
    }

    .action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .btn {
        border: none;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-primary {
        background: #2563eb;
        color: #fff;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .data-table th,
    .data-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        font-size: 13px;
        vertical-align: top;
    }

    .data-table th {
        background: #f9fafb;
        color: #374151;
        font-weight: 800;
    }

    .empty-box {
        padding: 16px;
        border: 1px dashed #d1d5db;
        border-radius: 14px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 14px;
    }

    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #eef2ff;
        color: #4338ca;
    }

    pre {
        margin: 0;
        white-space: pre-wrap;
        font-size: 12px;
    }

    @media (max-width: 1199px) {
        .stats-grid,
        .summary-grid,
        .two-col-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="stats-grid">
    <div class="card-box">
        <div class="card-label">Source Tables</div>
        <div class="card-value">{{ $stats['source_tables'] }}</div>
    </div>
    <div class="card-box">
        <div class="card-label">Destination Tables</div>
        <div class="card-value">{{ $stats['destination_tables'] }}</div>
    </div>
    <div class="card-box">
        <div class="card-label">Common Tables</div>
        <div class="card-value">{{ $stats['common_tables'] }}</div>
    </div>
    <div class="card-box">
        <div class="card-label">Compare Runs</div>
        <div class="card-value">{{ $stats['recent_compare_runs'] }}</div>
    </div>
</div>

<div class="two-col-grid">
    <div class="table-card">
        <h3>Source Database Tables</h3>
        @if(!empty($sourceInfo['tables']))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Table Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sourceInfo['tables'] as $table)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $table }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">{{ $sourceInfo['message'] ?? 'No source tables found.' }}</div>
        @endif
    </div>

    <div class="table-card">
        <h3>Destination Database Tables</h3>
        @if(!empty($destinationInfo['tables']))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Table Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($destinationInfo['tables'] as $table)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $table }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">{{ $destinationInfo['message'] ?? 'No destination tables found.' }}</div>
        @endif
    </div>
</div>

<div class="form-card" style="margin-bottom:22px;">
    <h3>Compare Table Data</h3>

    <form action="{{ route('admin.data-compare.run') }}" method="POST">
        @csrf

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Common Table</label>
                <select name="table_name" class="form-select" required>
                    <option value="">Select common table</option>
                    @foreach($commonTables as $table)
                        <option value="{{ $table }}" {{ old('table_name', $results['table_name'] ?? '') === $table ? 'selected' : '' }}>
                            {{ $table }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Primary Key Column</label>
                <input type="text" name="primary_key_column" class="form-control"
                       value="{{ old('primary_key_column', $results['primary_key_column'] ?? '') }}"
                       placeholder="Leave blank to auto detect">
            </div>

            <div class="form-group">
                <label class="form-label">Preview Row Limit</label>
                <input type="number" name="row_limit" class="form-control"
                       value="{{ old('row_limit', $results['summary']['row_limit'] ?? 500) }}"
                       min="1" max="5000" required>
            </div>

            <div class="form-group">
                <label class="form-label">Selected Columns</label>
                <input type="text" name="selected_columns" class="form-control"
                       value="{{ old('selected_columns', isset($results['compared_columns']) ? implode(', ', $results['compared_columns']) : '') }}"
                       placeholder="Optional: id, title, category">
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn btn-primary">Run Compare</button>
        </div>
    </form>
</div>

@if(!empty($results))
    <div class="summary-grid">
        <div class="card-box">
            <div class="card-label">Source Total</div>
            <div class="card-value">{{ $results['summary']['source_total_rows'] }}</div>
        </div>
        <div class="card-box">
            <div class="card-label">Destination Total</div>
            <div class="card-value">{{ $results['summary']['destination_total_rows'] }}</div>
        </div>
        <div class="card-box">
            <div class="card-label">Only In Source</div>
            <div class="card-value">{{ $results['summary']['only_in_source_count'] }}</div>
        </div>
        <div class="card-box">
            <div class="card-label">Only In Destination</div>
            <div class="card-value">{{ $results['summary']['only_in_destination_count'] }}</div>
        </div>
        <div class="card-box">
            <div class="card-label">Changed Rows</div>
            <div class="card-value">{{ $results['summary']['changed_rows_count'] }}</div>
        </div>
        <div class="card-box">
            <div class="card-label">Same Rows</div>
            <div class="card-value">{{ $results['summary']['same_rows_count'] }}</div>
        </div>
    </div>

    <div class="table-card" style="margin-bottom:22px;">
        <h3>Compare Summary</h3>
        <div class="table-wrapper">
            <table class="data-table">
                <tbody>
                    <tr>
                        <th>Table Name</th>
                        <td>{{ $results['table_name'] }}</td>
                    </tr>
                    <tr>
                        <th>Primary Key</th>
                        <td>{{ $results['primary_key_column'] }}</td>
                    </tr>
                    <tr>
                        <th>Compared Columns</th>
                        <td>{{ implode(', ', $results['compared_columns']) }}</td>
                    </tr>
                    <tr>
                        <th>Source Loaded Rows</th>
                        <td>{{ $results['summary']['source_loaded_rows'] }}</td>
                    </tr>
                    <tr>
                        <th>Destination Loaded Rows</th>
                        <td>{{ $results['summary']['destination_loaded_rows'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card" style="margin-bottom:22px;">
        <h3>Rows Only In Source</h3>
        @if(count($results['only_in_source']))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Row Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['only_in_source'] as $row)
                            <tr>
                                <td><pre>{{ json_encode($row, JSON_PRETTY_PRINT) }}</pre></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No rows found only in source.</div>
        @endif
    </div>

    <div class="table-card" style="margin-bottom:22px;">
        <h3>Rows Only In Destination</h3>
        @if(count($results['only_in_destination']))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Row Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['only_in_destination'] as $row)
                            <tr>
                                <td><pre>{{ json_encode($row, JSON_PRETTY_PRINT) }}</pre></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No rows found only in destination.</div>
        @endif
    </div>

    <div class="table-card" style="margin-bottom:22px;">
        <h3>Changed Rows</h3>
        @if(count($results['changed_rows']))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Different Columns</th>
                            <th>Source Row</th>
                            <th>Destination Row</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['changed_rows'] as $row)
                            <tr>
                                <td>{{ $row['key'] }}</td>
                                <td>{{ implode(', ', $row['different_columns']) }}</td>
                                <td><pre>{{ json_encode($row['source_row'], JSON_PRETTY_PRINT) }}</pre></td>
                                <td><pre>{{ json_encode($row['destination_row'], JSON_PRETTY_PRINT) }}</pre></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No changed rows found.</div>
        @endif
    </div>

    <div class="two-col-grid">
        <div class="table-card">
            <h3>Source Rows Preview</h3>
            @if(count($results['source_rows']))
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Row Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['source_rows'] as $row)
                                <tr>
                                    <td><pre>{{ json_encode($row, JSON_PRETTY_PRINT) }}</pre></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-box">No source rows loaded.</div>
            @endif
        </div>

        <div class="table-card">
            <h3>Destination Rows Preview</h3>
            @if(count($results['destination_rows']))
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Row Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['destination_rows'] as $row)
                                <tr>
                                    <td><pre>{{ json_encode($row, JSON_PRETTY_PRINT) }}</pre></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-box">No destination rows loaded.</div>
            @endif
        </div>
    </div>
@endif

<div class="table-card">
    <h3>Recent Compare Runs</h3>

    @if($recentRuns->count())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Table</th>
                        <th>Primary Key</th>
                        <th>Source Rows</th>
                        <th>Destination Rows</th>
                        <th>Only In Source</th>
                        <th>Only In Destination</th>
                        <th>Changed</th>
                        <th>Same</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Started</th>
                        <th>Ended</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRuns as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->table_name }}</td>
                            <td>{{ $row->primary_key_column ?? '-' }}</td>
                            <td>{{ $row->source_loaded_rows }}</td>
                            <td>{{ $row->destination_loaded_rows }}</td>
                            <td>{{ $row->only_in_source_count }}</td>
                            <td>{{ $row->only_in_destination_count }}</td>
                            <td>{{ $row->changed_rows_count }}</td>
                            <td>{{ $row->same_rows_count }}</td>
                            <td><span class="badge">{{ $row->status }}</span></td>
                            <td>{{ $row->message }}</td>
                            <td>{{ $row->started_at }}</td>
                            <td>{{ $row->ended_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-box">No compare history found.</div>
    @endif
</div>
@endsection