@extends('admin.layouts.app')

@section('title', 'Manual Transfer - Bolt Sync Admin')
@section('page_title', 'Manual Transfer')
@section('page_subtitle', 'Transfer data manually in either direction: Source → Destination or Destination → Source')

@push('styles')
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .stat-card,
        .panel-card,
        .form-card,
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
        }

        .stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .stat-value {
            font-size: 32px;
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

        .panel-card h3,
        .form-card h3,
        .table-card h3 {
            font-size: 18px;
            margin-bottom: 16px;
            color: #111827;
        }

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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
        .form-select,
        .form-textarea {
            width: 100%;
            min-height: 46px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            background: #fff;
        }

        .form-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .checkbox-row {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 8px;
        }

        .check-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
            font-weight: 600;
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

        .btn-success {
            background: #059669;
            color: #fff;
        }

        .btn-dark {
            background: #111827;
            color: #fff;
        }

        .empty-box {
            padding: 16px;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 14px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
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

        .direction-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 600;
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

        @media (max-width: 1199px) {

            .stats-grid,
            .two-col-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Source Tables</div>
            <div class="stat-value">{{ $stats['source_tables'] }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Destination Tables</div>
            <div class="stat-value">{{ $stats['destination_tables'] }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Active Mappings</div>
            <div class="stat-value">{{ $stats['active_mappings'] }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Runs</div>
            <div class="stat-value">{{ $stats['recent_runs'] }}</div>
        </div>
    </div>

    <div class="two-col-grid">
        <div class="panel-card">
            <h3>Source Database Tables</h3>

            @if (!empty($sourceInfo['tables']))
                <div class="detail-list">
                    @foreach ($sourceInfo['tables'] as $table)
                        <div class="detail-row">
                            <span class="detail-label">{{ $loop->iteration }}</span>
                            <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-box">{{ $sourceInfo['message'] ?? 'No source tables found.' }}</div>
            @endif
        </div>

        <div class="panel-card">
            <h3>Destination Database Tables</h3>

            @if (!empty($destinationInfo['tables']))
                <div class="detail-list">
                    @foreach ($destinationInfo['tables'] as $table)
                        <div class="detail-row">
                            <span class="detail-label">{{ $loop->iteration }}</span>
                            <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-box">{{ $destinationInfo['message'] ?? 'No destination tables found.' }}</div>
            @endif
        </div>
    </div>

    <div class="form-card" style="margin-bottom:22px;">
        <h3>Create / Update Manual Transfer Mapping</h3>

        <div class="direction-box">
            Choose the transfer direction first.
            If you choose <strong>Destination → Source</strong>, data will be read from the destination table and inserted
            into the source table.
        </div>

        <form action="{{ route('admin.sync-jobs.save-mapping') }}" method="POST">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Mapping Name</label>
                    <input type="text" name="name" class="form-control"
                        placeholder="Example: Tasks Destination to Source" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Transfer Direction</label>
                    <select name="transfer_direction" class="form-select" required>
                        <option value="source_to_destination">Source → Destination</option>
                        <option value="destination_to_source">Destination → Source</option>
                    </select>
                    <div class="form-note">Select how data should move.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Sync Mode</label>
                    <select name="sync_mode" class="form-select" required>
                        <option value="mirror">Mirror (clear target table, then copy)</option>
                        <option value="append">Append (insert only)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Primary Key Column</label>
                    <input type="text" name="primary_key_column" class="form-control" placeholder="Example: id">
                </div>

                <div class="form-group">
                    <label class="form-label">Source Table</label>
                    <select name="source_table_name" class="form-select" required>
                        <option value="">Select source table</option>
                        @foreach ($sourceInfo['tables'] ?? [] as $table)
                            <option value="{{ $table }}">{{ $table }}</option>
                        @endforeach
                    </select>
                    <div class="form-note">Used as target when direction is Destination → Source.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Destination Table</label>
                    <select name="destination_table_name" class="form-select" required>
                        <option value="">Select destination table</option>
                        @foreach ($destinationInfo['tables'] ?? [] as $table)
                            <option value="{{ $table }}">{{ $table }}</option>
                        @endforeach
                    </select>
                    <div class="form-note">Used as source when direction is Destination → Source.</div>
                </div>

                <div class="form-group full">
                    <label class="form-label">Selected Columns</label>
                    <input type="text" name="selected_columns" class="form-control"
                        placeholder="Example: id, title, status, created_at">
                    <div class="form-note">Optional. Leave blank to use all common columns between both tables.</div>
                </div>

                <div class="form-group full">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-textarea" placeholder="Optional transfer notes"></textarea>
                </div>

                <div class="form-group full">
                    <label class="form-label">Options</label>
                    <div class="checkbox-row">
                        <label class="check-item">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Active Mapping
                        </label>
                    </div>
                </div>
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-primary">Save Mapping</button>
            </div>
        </form>
    </div>

    <div class="table-card" style="margin-bottom:22px;">
        <h3>Saved Transfer Mappings</h3>

        @if ($mappings->count())
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Direction</th>
                            <th>Source Table</th>
                            <th>Destination Table</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Last Synced</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mappings as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->name }}</td>
                                <td>
                                    <span class="badge">
                                        {{ ($row->transfer_direction ?? 'source_to_destination') === 'destination_to_source' ? 'Destination → Source' : 'Source → Destination' }}
                                    </span>
                                </td>
                                <td>{{ $row->source_table_name }}</td>
                                <td>{{ $row->destination_table_name }}</td>
                                <td>{{ $row->sync_mode }}</td>
                                <td>{{ $row->last_sync_status ?? '-' }}</td>
                                <td>{{ $row->last_synced_at ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('admin.sync-jobs.run') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mapping_id" value="{{ $row->id }}">
                                        <button type="submit" class="btn btn-success">Run Transfer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No transfer mappings found.</div>
        @endif
    </div>

    <div class="table-card" style="margin-bottom:22px;">
        <h3>Recent Transfer Runs</h3>

        @if ($recentRuns->count())
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Module</th>
                            <th>Run Type</th>
                            <th>Source Table</th>
                            <th>Destination Table</th>
                            <th>Status</th>
                            <th>Rows</th>
                            <th>Started At</th>
                            <th>Ended At</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentRuns as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->module_name ?? '-' }}</td>
                                <td>{{ $row->run_type ?? '-' }}</td>
                                <td>{{ $row->source_table_name ?? '-' }}</td>
                                <td>{{ $row->destination_table_name ?? '-' }}</td>
                                <td>{{ $row->status ?? '-' }}</td>
                                <td>{{ $row->records_processed ?? 0 }}</td>
                                <td>{{ $row->started_at ?? '-' }}</td>
                                <td>{{ $row->ended_at ?? '-' }}</td>
                                <td>{{ $row->message ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No transfer runs found.</div>
        @endif
    </div>

    <div class="table-card">
        <h3>Recent Transfer Logs</h3>

        @if ($recentLogs->count())
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Run ID</th>
                            <th>Level</th>
                            <th>Message</th>
                            <th>Context</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentLogs as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->sync_run_id ?? '-' }}</td>
                                <td>{{ $row->level ?? '-' }}</td>
                                <td>{{ $row->message ?? '-' }}</td>
                                <td>
                                    @if (!empty($row->context))
                                        <pre style="white-space:pre-wrap; margin:0;">{{ json_encode($row->context, JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $row->created_at ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No transfer logs found.</div>
        @endif
    </div>
@endsection
