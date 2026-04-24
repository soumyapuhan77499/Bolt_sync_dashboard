@extends('admin.layouts.app')

@section('title', 'Audit Logs - Bolt Sync Admin')
@section('page_title', 'Audit Logs')
@section('page_subtitle', 'Track admin actions and system activity')

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .filter-card,
    .stat-card,
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .filter-card {
        margin-bottom: 22px;
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

    .table-card h3,
    .filter-card h3 {
        font-size: 18px;
        margin-bottom: 16px;
        color: #111827;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
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
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-primary { background: #2563eb; color: #fff; }
    .btn-light { background: #f3f4f6; color: #111827; }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
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
    .status-dark { background: #e5e7eb; color: #111827; }

    .empty-box {
        padding: 16px;
        border: 1px dashed #d1d5db;
        border-radius: 14px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 14px;
    }

    pre {
        margin: 0;
        white-space: pre-wrap;
        font-size: 12px;
    }

    .pagination-wrap {
        margin-top: 16px;
    }

    @media (max-width: 1199px) {
        .stats-grid,
        .filter-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Logs</div>
        <div class="stat-value">{{ $stats['total_logs'] ?? 0 }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Success Logs</div>
        <div class="stat-value">{{ $stats['success_logs'] ?? 0 }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Failed Logs</div>
        <div class="stat-value">{{ $stats['failed_logs'] ?? 0 }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Today Logs</div>
        <div class="stat-value">{{ $stats['today_logs'] ?? 0 }}</div>
    </div>
</div>

<div class="filter-card">
    <h3>Filter Audit Logs</h3>

    <form method="GET" action="{{ route('admin.audit.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label class="form-label">Module</label>
                <select name="module_name" class="form-select">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" {{ request('module_name') === $module ? 'selected' : '' }}>{{ $module }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Action</label>
                <select name="action_name" class="form-select">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action_name') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Admin User ID</label>
                <input type="text" name="admin_user_id" class="form-control" value="{{ request('admin_user_id') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Keyword</label>
                <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}">
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="{{ route('admin.audit.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>
</div>

<div class="table-card">
    <h3>Audit Log List</h3>

    @if($logs->count())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Admin</th>
                        <th>IP</th>
                        <th>User Agent</th>
                        <th>Context</th>
                        <th>Logged At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->module_name ?? '-' }}</td>
                            <td>{{ $row->action_name ?? '-' }}</td>
                            <td>{{ $row->description ?? '-' }}</td>
                            <td>
                                <span class="status-chip {{ ($row->status ?? '') === 'success' ? 'status-success' : (($row->status ?? '') === 'failed' ? 'status-danger' : 'status-dark') }}">
                                    {{ $row->status ?? '-' }}
                                </span>
                            </td>
                            <td>
                                {{ $row->admin_name ?? '-' }}<br>
                                <small>{{ $row->admin_user_id ?? '-' }}</small>
                            </td>
                            <td>{{ $row->ip_address ?? '-' }}</td>
                            <td>{{ $row->user_agent ?? '-' }}</td>
                            <td>
                                @if(!empty($row->context))
                                    <pre>{{ json_encode($row->context, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $row->logged_at ?? $row->created_at ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $logs->links() }}
        </div>
    @else
        <div class="empty-box">No audit logs found.</div>
    @endif
</div>
@endsection