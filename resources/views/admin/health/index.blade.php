@extends('admin.layouts.app')

@section('title', 'Health - Bolt Sync Admin')
@section('page_title', 'Health')
@section('page_subtitle', 'Monitor source, destination, and admin database health')

@php
    $statusClass = function ($status) {
        return match($status) {
            'connected', 'success' => 'status-success',
            'failed', 'error' => 'status-danger',
            default => 'status-dark',
        };
    };
@endphp

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .section-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .stat-card,
    .panel-card,
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

    .panel-card h3,
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

    .action-row {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
    }

    .btn {
        border: none;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        background: #2563eb;
        color: #fff;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
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
        .stats-grid,
        .section-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="action-row">
    <a href="{{ route('admin.health.index', ['refresh' => 1]) }}" class="btn">Refresh Health Checks</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Checks</div>
        <div class="stat-value">{{ $stats['total_checks'] ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Successful</div>
        <div class="stat-value">{{ $stats['successful_checks'] ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value">{{ $stats['failed_checks'] ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Healthy Targets</div>
        <div class="stat-value">{{ $stats['live_healthy_targets'] ?? 0 }}</div>
    </div>
</div>

<div class="section-grid">
    <div class="panel-card">
        <h3>Source Database</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $statusClass($sourceHealth['status'] ?? '') }}">{{ $sourceHealth['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $sourceHealth['database_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $sourceHealth['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tables</span>
                <span class="detail-value">{{ $sourceHealth['tables_count'] ?? 0 }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $sourceHealth['message'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Checked At</span>
                <span class="detail-value">{{ $sourceHealth['checked_at'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <h3>Destination Database</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $statusClass($destinationHealth['status'] ?? '') }}">{{ $destinationHealth['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $destinationHealth['database_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $destinationHealth['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tables</span>
                <span class="detail-value">{{ $destinationHealth['tables_count'] ?? 0 }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $destinationHealth['message'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Checked At</span>
                <span class="detail-value">{{ $destinationHealth['checked_at'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <h3>Admin Database</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $statusClass($adminHealth['status'] ?? '') }}">{{ $adminHealth['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $adminHealth['database_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $adminHealth['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tables</span>
                <span class="detail-value">{{ $adminHealth['tables_count'] ?? 0 }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $adminHealth['message'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Checked At</span>
                <span class="detail-value">{{ $adminHealth['checked_at'] ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <h3>Recent Health Checks</h3>

    @if(!empty($recentHealthChecks) && count($recentHealthChecks))
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Target</th>
                        <th>Check Type</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Checked At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentHealthChecks as $row)
                        <tr>
                            <td>{{ $row->id ?? '-' }}</td>
                            <td>{{ $row->target_name ?? '-' }}</td>
                            <td>{{ $row->check_type ?? '-' }}</td>
                            <td>{{ $row->status ?? '-' }}</td>
                            <td>{{ $row->message ?? '-' }}</td>
                            <td>{{ $row->checked_at ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-box">No health check records found.</div>
    @endif
</div>
@endsection