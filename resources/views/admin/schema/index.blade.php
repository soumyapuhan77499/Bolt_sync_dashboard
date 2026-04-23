@extends('admin.layouts.app')

@section('title', 'Schema Diff - Bolt Sync Admin')
@section('page_title', 'Schema Diff')
@section('page_subtitle', 'Capture schema snapshots, compare source and destination, and apply missing schema changes')

@push('styles')
<style>
    .schema-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .schema-card,
    .schema-table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .schema-card h3,
    .schema-table-card h3 {
        font-size: 18px;
        margin-bottom: 16px;
        color: #111827;
    }

    .schema-form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 22px;
    }

    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
        margin-bottom: 22px;
    }

    .form-card h3 {
        font-size: 18px;
        margin-bottom: 16px;
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

    .btn-primary { background: #2563eb; color: #fff; }
    .btn-dark { background: #111827; color: #fff; }
    .btn-success { background: #059669; color: #fff; }

    .status-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .status-connected { background: #dcfce7; color: #166534; }
    .status-failed { background: #fee2e2; color: #991b1b; }

    .detail-list {
        display: grid;
        gap: 10px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #e5e7eb;
    }

    .detail-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .detail-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 700;
    }

    .detail-value {
        color: #111827;
        font-size: 13px;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .summary-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .summary-value {
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        line-height: 1;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 780px;
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

    .code-box {
        white-space: pre-wrap;
        background: #0f172a;
        color: #e5e7eb;
        border-radius: 14px;
        padding: 16px;
        overflow-x: auto;
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 1199px) {
        .schema-grid,
        .summary-grid,
        .schema-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <div class="form-card">
        <h3>Create Snapshot</h3>

        <form action="{{ route('admin.schema.snapshot') }}" method="POST">
            @csrf

            <div class="schema-form-grid">
                <div class="form-group">
                    <label class="form-label">Target</label>
                    <select name="target_name" class="form-select" required>
                        <option value="source">Source</option>
                        <option value="destination">Destination</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 3;">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes for this snapshot">
                </div>
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-primary">Create Snapshot</button>
            </div>
        </form>
    </div>

    <div class="form-card">
        <h3>Compare Live Schemas</h3>

        <form action="{{ route('admin.schema.diff') }}" method="POST">
            @csrf

            <div class="schema-form-grid">
                <div class="form-group">
                    <label class="form-label">From</label>
                    <select name="from_target" class="form-select" required>
                        <option value="source" {{ $fromTarget === 'source' ? 'selected' : '' }}>Source</option>
                        <option value="destination" {{ $fromTarget === 'destination' ? 'selected' : '' }}>Destination</option>
                        <option value="admin" {{ $fromTarget === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">To</label>
                    <select name="to_target" class="form-select" required>
                        <option value="destination" {{ $toTarget === 'destination' ? 'selected' : '' }}>Destination</option>
                        <option value="source" {{ $toTarget === 'source' ? 'selected' : '' }}>Source</option>
                        <option value="admin" {{ $toTarget === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-dark">Compare Schemas</button>
            </div>
        </form>
    </div>

    <div class="schema-grid">
        @foreach($liveSchemas as $key => $item)
            <div class="schema-card">
                <h3>{{ ucfirst($key) }} Schema</h3>

                <div class="detail-list">
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-chip {{ $item['status'] === 'connected' ? 'status-connected' : 'status-failed' }}">
                                {{ $item['status'] }}
                            </span>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Database</span>
                        <span class="detail-value">{{ $item['snapshot']['database_name'] ?? '-' }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Schema</span>
                        <span class="detail-value">{{ $item['snapshot']['schema_name'] ?? '-' }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Tables</span>
                        <span class="detail-value">{{ $item['snapshot']['tables_count'] ?? 0 }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Message</span>
                        <span class="detail-value">{{ $item['message'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($diffResult)
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Missing Tables in {{ ucfirst($diffResult['destination_target']) }}</div>
                <div class="summary-value">{{ $diffResult['summary']['missing_tables_count'] }}</div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Extra Tables in {{ ucfirst($diffResult['destination_target']) }}</div>
                <div class="summary-value">{{ $diffResult['summary']['extra_tables_count'] }}</div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Changed Tables</div>
                <div class="summary-value">{{ $diffResult['summary']['changed_tables_count'] }}</div>
            </div>
        </div>

        <div class="schema-table-card" style="margin-bottom:22px;">
            <h3>Apply Missing Schema From {{ ucfirst($fromTarget) }} To {{ ucfirst($toTarget) }}</h3>

            <form action="{{ route('admin.schema.apply') }}" method="POST">
                @csrf
                <input type="hidden" name="from_target" value="{{ $fromTarget }}">
                <input type="hidden" name="to_target" value="{{ $toTarget }}">
                <input type="hidden" name="execute" value="1">

                <div class="action-row">
                    <button type="submit" class="btn btn-success">Apply Missing Tables / Columns</button>
                </div>
            </form>
        </div>

        <div class="schema-grid">
            <div class="schema-table-card">
                <h3>Missing Tables In {{ ucfirst($toTarget) }}</h3>

                @if(!empty($diffResult['missing_tables_in_target']))
                    <div class="detail-list">
                        @foreach($diffResult['missing_tables_in_target'] as $table)
                            <div class="detail-row">
                                <span class="detail-label">{{ $loop->iteration }}</span>
                                <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-box">No missing tables found.</div>
                @endif
            </div>

            <div class="schema-table-card">
                <h3>Extra Tables In {{ ucfirst($toTarget) }}</h3>

                @if(!empty($diffResult['extra_tables_in_target']))
                    <div class="detail-list">
                        @foreach($diffResult['extra_tables_in_target'] as $table)
                            <div class="detail-row">
                                <span class="detail-label">{{ $loop->iteration }}</span>
                                <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-box">No extra tables found.</div>
                @endif
            </div>

            <div class="schema-table-card">
                <h3>Changed Tables</h3>

                @if(!empty($diffResult['table_diffs']))
                    <div class="detail-list">
                        @foreach($diffResult['table_diffs'] as $table => $meta)
                            <div class="detail-row">
                                <span class="detail-label">{{ $table }}</span>
                                <span class="detail-value">
                                    Missing: {{ count($meta['missing_columns']) }},
                                    Extra: {{ count($meta['extra_columns']) }},
                                    Changed: {{ count($meta['changed_columns']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-box">No changed tables found.</div>
                @endif
            </div>
        </div>

        <div class="schema-table-card" style="margin-bottom:22px;">
            <h3>Detailed Column Differences</h3>

            @if(!empty($diffResult['table_diffs']))
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Missing Columns In {{ ucfirst($toTarget) }}</th>
                                <th>Extra Columns In {{ ucfirst($toTarget) }}</th>
                                <th>Changed Columns</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($diffResult['table_diffs'] as $table => $meta)
                                <tr>
                                    <td>{{ $table }}</td>
                                    <td>{{ !empty($meta['missing_columns']) ? implode(', ', $meta['missing_columns']) : '-' }}</td>
                                    <td>{{ !empty($meta['extra_columns']) ? implode(', ', $meta['extra_columns']) : '-' }}</td>
                                    <td>
                                        @if(!empty($meta['changed_columns']))
                                            @foreach($meta['changed_columns'] as $columnName => $columnDiff)
                                                <div style="margin-bottom:8px;">
                                                    <strong>{{ $columnName }}</strong><br>
                                                    Source: {{ $columnDiff['source']['data_type'] ?? '-' }}
                                                    @if(!empty($columnDiff['source']['character_maximum_length']))
                                                        ({{ $columnDiff['source']['character_maximum_length'] }})
                                                    @endif
                                                    | Nullable: {{ $columnDiff['source']['is_nullable'] ?? '-' }}<br>
                                                    Target: {{ $columnDiff['target']['data_type'] ?? '-' }}
                                                    @if(!empty($columnDiff['target']['character_maximum_length']))
                                                        ({{ $columnDiff['target']['character_maximum_length'] }})
                                                    @endif
                                                    | Nullable: {{ $columnDiff['target']['is_nullable'] ?? '-' }}
                                                </div>
                                            @endforeach
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
                <div class="empty-box">No column differences found.</div>
            @endif
        </div>
    @endif

    @if(session('generated_schema_sql'))
        <div class="schema-table-card" style="margin-bottom:22px;">
            <h3>Generated / Applied SQL</h3>
            <div class="code-box">{{ session('generated_schema_sql') }}</div>
        </div>
    @endif

    <div class="schema-table-card">
        <h3>Recent Schema Snapshots</h3>

        @if($recentSnapshots->count())
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Target</th>
                            <th>Database</th>
                            <th>Schema</th>
                            <th>Captured At</th>
                            <th>Created By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSnapshots as $snapshot)
                            <tr>
                                <td>{{ $snapshot->id }}</td>
                                <td>{{ ucfirst($snapshot->target_name) }}</td>
                                <td>{{ $snapshot->database_name }}</td>
                                <td>{{ $snapshot->schema_name }}</td>
                                <td>{{ $snapshot->created_at }}</td>
                                <td>{{ $snapshot->created_by ?? '-' }}</td>
                                <td>{{ $snapshot->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No schema snapshots found.</div>
        @endif
    </div>
@endsection