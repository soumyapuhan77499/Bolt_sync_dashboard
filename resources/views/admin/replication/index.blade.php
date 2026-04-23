@extends('admin.layouts.app')

@section('title', 'Replication - Bolt Sync Admin')
@section('page_title', 'Replication')
@section('page_subtitle', 'Configure replication metadata, check connectivity, and manage replication run state')

@push('styles')
<style>
    .replication-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .panel-card,
    .form-card,
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
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
        gap: 12px;
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

    .status-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .status-success { background: #dcfce7; color: #166534; }
    .status-danger { background: #fee2e2; color: #991b1b; }
    .status-warning { background: #fef3c7; color: #92400e; }
    .status-dark { background: #e5e7eb; color: #111827; }

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

    .btn-primary { background: #2563eb; color: #fff; }
    .btn-success { background: #059669; color: #fff; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-dark { background: #111827; color: #fff; }

    .meta-box {
        margin-bottom: 18px;
        border: 1px dashed #d1d5db;
        background: #f9fafb;
        border-radius: 14px;
        padding: 14px;
        font-size: 13px;
        color: #4b5563;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
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

    @media (max-width: 1199px) {
        .replication-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $configStatusClass = match($currentConfig->status ?? 'draft') {
        'running' => 'status-success',
        'failed' => 'status-danger',
        'stopped' => 'status-warning',
        default => 'status-dark',
    };

    $sourceStatusClass = ($sourceStatus['status'] ?? '') === 'connected' ? 'status-success' : 'status-danger';
    $destinationStatusClass = ($destinationStatus['status'] ?? '') === 'connected' ? 'status-success' : 'status-danger';
@endphp

<div class="replication-grid">
    <div class="panel-card">
        <h3>Source Connection</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $sourceStatusClass }}">{{ $sourceStatus['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ $sourceConnection['name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Host</span>
                <span class="detail-value">{{ $sourceConnection['host'] ?? '-' }}:{{ $sourceConnection['port'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $sourceStatus['database_name'] ?? ($sourceConnection['database_name'] ?? '-') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $sourceConnection['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $sourceStatus['message'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <h3>Destination Connection</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $destinationStatusClass }}">{{ $destinationStatus['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ $destinationConnection['name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Host</span>
                <span class="detail-value">{{ $destinationConnection['host'] ?? '-' }}:{{ $destinationConnection['port'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $destinationStatus['database_name'] ?? ($destinationConnection['database_name'] ?? '-') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $destinationConnection['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $destinationStatus['message'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <h3>Current Replication State</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Config Name</span>
                <span class="detail-value">{{ $currentConfig->name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $configStatusClass }}">{{ $currentConfig->status ?? 'draft' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Publication</span>
                <span class="detail-value">{{ $currentConfig->publication_name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Subscription</span>
                <span class="detail-value">{{ $currentConfig->subscription_name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Last Checked</span>
                <span class="detail-value">{{ $currentConfig->last_checked_at ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $currentConfig->last_message ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="form-card">
    <h3>Replication Configuration</h3>

    <div class="meta-box">
        This page stores replication configuration and validates source and destination database connectivity.
        Source and destination connection details come from your saved connection records.
    </div>

    <form action="{{ route('admin.replication.store') }}" method="POST">
        @csrf
        <input type="hidden" name="config_id" value="{{ $currentConfig->id ?? '' }}">

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Config Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $currentConfig->name ?? 'Default Replication Config') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Mode</label>
                <select name="replication_mode" class="form-select" required>
                    <option value="logical" {{ old('replication_mode', $currentConfig->replication_mode ?? 'logical') === 'logical' ? 'selected' : '' }}>Logical</option>
                    <option value="manual" {{ old('replication_mode', $currentConfig->replication_mode ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Publication Name</label>
                <input type="text" name="publication_name" class="form-control" value="{{ old('publication_name', $currentConfig->publication_name ?? 'bolt_pub') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Subscription Name</label>
                <input type="text" name="subscription_name" class="form-control" value="{{ old('subscription_name', $currentConfig->subscription_name ?? 'bolt_sub') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Source Schema</label>
                <input type="text" name="source_schema_name" class="form-control" value="{{ old('source_schema_name', $currentConfig->source_schema_name ?? ($sourceConnection['schema_name'] ?? 'public')) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Destination Schema</label>
                <input type="text" name="destination_schema_name" class="form-control" value="{{ old('destination_schema_name', $currentConfig->destination_schema_name ?? ($destinationConnection['schema_name'] ?? 'public')) }}" required>
            </div>

            <div class="form-group full">
                <label class="form-label">Source Tables (comma separated)</label>
                <input type="text" name="source_tables" class="form-control" value="{{ old('source_tables', $currentConfig->source_tables ?? '') }}" placeholder="profiles, tasks, field_updates">
            </div>

            <div class="form-group full">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-textarea" placeholder="Optional notes">{{ old('notes', $currentConfig->notes ?? '') }}</textarea>
            </div>

            <div class="form-group full">
                <label class="form-label">Options</label>
                <div class="checkbox-row">
                    <label class="check-item">
                        <input type="checkbox" name="sync_inserts" value="1" {{ old('sync_inserts', $currentConfig->sync_inserts ?? true) ? 'checked' : '' }}>
                        Sync Inserts
                    </label>

                    <label class="check-item">
                        <input type="checkbox" name="sync_updates" value="1" {{ old('sync_updates', $currentConfig->sync_updates ?? true) ? 'checked' : '' }}>
                        Sync Updates
                    </label>

                    <label class="check-item">
                        <input type="checkbox" name="sync_deletes" value="1" {{ old('sync_deletes', $currentConfig->sync_deletes ?? false) ? 'checked' : '' }}>
                        Sync Deletes
                    </label>

                    <label class="check-item">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $currentConfig->is_active ?? true) ? 'checked' : '' }}>
                        Active
                    </label>
                </div>
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn btn-primary">Save Replication Config</button>
        </div>
    </form>

    <div class="action-row">
        <form action="{{ route('admin.replication.start') }}" method="POST">
            @csrf
            <input type="hidden" name="config_id" value="{{ $currentConfig->id ?? '' }}">
            <button type="submit" class="btn btn-success">Start Replication</button>
        </form>

        <form action="{{ route('admin.replication.stop') }}" method="POST">
            @csrf
            <input type="hidden" name="config_id" value="{{ $currentConfig->id ?? '' }}">
            <button type="submit" class="btn btn-danger">Stop Replication</button>
        </form>

        <form action="{{ route('admin.replication.status') }}" method="GET">
            <input type="hidden" name="config_id" value="{{ $currentConfig->id ?? '' }}">
            <button type="submit" class="btn btn-dark">Check Status</button>
        </form>
    </div>
</div>

<div class="table-card">
    <h3>Recent Replication Configs</h3>

    @if($recentConfigs->count())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mode</th>
                        <th>Publication</th>
                        <th>Subscription</th>
                        <th>Status</th>
                        <th>Tables</th>
                        <th>Started At</th>
                        <th>Stopped At</th>
                        <th>Last Checked</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentConfigs as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->replication_mode }}</td>
                            <td>{{ $row->publication_name }}</td>
                            <td>{{ $row->subscription_name }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->source_tables ?? '-' }}</td>
                            <td>{{ $row->started_at ?? '-' }}</td>
                            <td>{{ $row->stopped_at ?? '-' }}</td>
                            <td>{{ $row->last_checked_at ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-box">No replication configuration found.</div>
    @endif
</div>
@endsection