@extends('admin.layouts.app')

@section('title', 'Data Compare - Bolt Sync Admin')
@section('page_title', 'Data Compare')
@section('page_subtitle', 'Compare source and destination rows with clean tabular view and table row count overview')

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
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
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        line-height: 1;
    }

    .table-card h3,
    .form-card h3 {
        font-size: 20px;
        margin-bottom: 14px;
        color: #111827;
    }

    .helper-text {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 16px;
        line-height: 1.6;
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

    .form-label {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
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

    .btn-light {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .search-box {
        margin-bottom: 16px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .data-table.compact {
        min-width: 700px;
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
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .empty-box {
        padding: 16px;
        border: 1px dashed #d1d5db;
        border-radius: 14px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 14px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-matched { background: #dcfce7; color: #166534; }
    .status-mismatch { background: #fef3c7; color: #92400e; }
    .status-only-source { background: #dbeafe; color: #1d4ed8; }
    .status-only-destination { background: #ede9fe; color: #6d28d9; }

    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #eef2ff;
        color: #4338ca;
    }

    .two-col-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .diff-stack {
        display: grid;
        gap: 8px;
    }

    .diff-item {
        padding: 10px 12px;
        border-radius: 12px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .diff-label {
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
    }

    .diff-source {
        color: #1d4ed8;
        font-size: 12px;
        line-height: 1.5;
    }

    .diff-destination {
        color: #7c2d12;
        font-size: 12px;
        line-height: 1.5;
    }

    .section-gap {
        margin-bottom: 22px;
    }

    @media (max-width: 1399px) {
        .stats-grid,
        .summary-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 1199px) {
        .form-grid,
        .two-col-grid,
        .stats-grid,
        .summary-grid {
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
        <div class="card-label">Source Rows</div>
        <div class="card-value">{{ $stats['source_total_rows'] }}</div>
    </div>
    <div class="card-box">
        <div class="card-label">Destination Tables</div>
        <div class="card-value">{{ $stats['destination_tables'] }}</div>
    </div>
    <div class="card-box">
        <div class="card-label">Destination Rows</div>
        <div class="card-value">{{ $stats['destination_total_rows'] }}</div>
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

<div class="table-card section-gap">
    <h3>All Database Tables Overview</h3>
    <div class="helper-text">
        This table shows source and destination database tables together with row counts, so you can quickly identify missing tables and row count mismatches.
    </div>

    <div class="search-box">
        <input type="text" id="tableSearchInput" class="form-control" placeholder="Search table name...">
    </div>

    @if(!empty($tableOverview))
        <div class="table-wrapper">
            <table class="data-table compact" id="tableOverviewTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Table Name</th>
                        <th>Source Rows</th>
                        <th>Destination Rows</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableOverview as $row)
                        <tr class="table-overview-row" data-table-name="{{ strtolower($row['table_name']) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row['table_name'] }}</strong></td>
                            <td>{{ $row['source_exists'] ? $row['source_row_count'] : '-' }}</td>
                            <td>{{ $row['destination_exists'] ? $row['destination_row_count'] : '-' }}</td>
                            <td>
                                @if($row['status'] === 'matched_count')
                                    <span class="status-badge status-matched">Matched Count</span>
                                @elseif($row['status'] === 'count_mismatch')
                                    <span class="status-badge status-mismatch">Count Mismatch</span>
                                @elseif($row['status'] === 'only_source')
                                    <span class="status-badge status-only-source">Only In Source</span>
                                @elseif($row['status'] === 'only_destination')
                                    <span class="status-badge status-only-destination">Only In Destination</span>
                                @else
                                    <span class="badge">Unknown</span>
                                @endif
                            </td>
                            <td>
                                @if($row['is_common'])
                                    <button type="button"
                                        class="btn btn-light quick-compare-btn"
                                        data-table="{{ $row['table_name'] }}">
                                        Compare
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-box">No database tables found.</div>
    @endif
</div>

<div class="form-card section-gap">
    <h3>Compare Table Data</h3>
    <div class="helper-text">
        Select a common table, choose primary key column, and compare preview rows in a proper tabular format.
    </div>

    <form action="{{ route('admin.data-compare.run') }}" method="POST">
        @csrf

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Common Table</label>
                <select name="table_name" id="compareTableSelect" class="form-select" required>
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
    @php
        $previewColumns = $results['compared_columns'] ?? [];
    @endphp

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
            <div class="card-label">Loaded Source</div>
            <div class="card-value">{{ $results['summary']['source_loaded_rows'] }}</div>
        </div>
        <div class="card-box">
            <div class="card-label">Loaded Destination</div>
            <div class="card-value">{{ $results['summary']['destination_loaded_rows'] }}</div>
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

    <div class="table-card section-gap">
        <h3>Compare Summary</h3>
        <div class="table-wrapper">
            <table class="data-table compact">
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
                        <td>{{ implode(', ', $previewColumns) }}</td>
                    </tr>
                    <tr>
                        <th>Only In Source</th>
                        <td>{{ $results['summary']['only_in_source_count'] }}</td>
                    </tr>
                    <tr>
                        <th>Only In Destination</th>
                        <td>{{ $results['summary']['only_in_destination_count'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="two-col-grid">
        <div class="table-card">
            <h3>Source Rows Preview</h3>
            @if(count($results['source_rows']))
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach($previewColumns as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['source_rows'] as $row)
                                <tr>
                                    @foreach($previewColumns as $column)
                                        <td>{{ $row[$column] ?? '-' }}</td>
                                    @endforeach
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
                                @foreach($previewColumns as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['destination_rows'] as $row)
                                <tr>
                                    @foreach($previewColumns as $column)
                                        <td>{{ $row[$column] ?? '-' }}</td>
                                    @endforeach
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

    <div class="two-col-grid">
        <div class="table-card">
            <h3>Rows Only In Source</h3>
            @if(count($results['only_in_source']))
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach($previewColumns as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['only_in_source'] as $row)
                                <tr>
                                    @foreach($previewColumns as $column)
                                        <td>{{ $row[$column] ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-box">No rows found only in source.</div>
            @endif
        </div>

        <div class="table-card">
            <h3>Rows Only In Destination</h3>
            @if(count($results['only_in_destination']))
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach($previewColumns as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['only_in_destination'] as $row)
                                <tr>
                                    @foreach($previewColumns as $column)
                                        <td>{{ $row[$column] ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-box">No rows found only in destination.</div>
            @endif
        </div>
    </div>

    <div class="table-card section-gap">
        <h3>Changed Rows</h3>
        @if(count($results['changed_rows']))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Different Columns</th>
                            <th>Value Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['changed_rows'] as $row)
                            <tr>
                                <td>{{ $row['key'] }}</td>
                                <td>{{ implode(', ', $row['different_columns']) }}</td>
                                <td>
                                    <div class="diff-stack">
                                        @foreach($row['different_columns'] as $column)
                                            <div class="diff-item">
                                                <div class="diff-label">{{ $column }}</div>
                                                <div class="diff-source">Source: {{ $row['source_row'][$column] ?? '-' }}</div>
                                                <div class="diff-destination">Destination: {{ $row['destination_row'][$column] ?? '-' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No changed rows found.</div>
        @endif
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

@push('scripts')
<script>
    const tableSearchInput = document.getElementById('tableSearchInput');
    const compareTableSelect = document.getElementById('compareTableSelect');
    const quickCompareButtons = document.querySelectorAll('.quick-compare-btn');

    if (tableSearchInput) {
        tableSearchInput.addEventListener('input', function () {
            const keyword = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.table-overview-row');

            rows.forEach(function (row) {
                const name = row.getAttribute('data-table-name') || '';
                row.style.display = name.includes(keyword) ? '' : 'none';
            });
        });
    }

    if (quickCompareButtons.length && compareTableSelect) {
        quickCompareButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                compareTableSelect.value = this.getAttribute('data-table');
                compareTableSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    }
</script>
@endpush