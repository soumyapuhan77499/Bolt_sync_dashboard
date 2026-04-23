@extends('admin.layouts.app')

@section('title', 'Dashboard - Bolt Sync Admin')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of database connections, health, backups, and recent activity')

@php
    $statusClass = function ($status) {
        return match($status) {
            'connected' => 'status-success',
            'success' => 'status-success',
            'failed' => 'status-danger',
            'error' => 'status-danger',
            default => 'status-warning',
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

    .stat-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 20px;
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

    .section-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .panel-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .panel-card h3 {
        font-size: 18px;
        margin-bottom: 18px;
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
        text-align: right;
        font-weight: 600;
        word-break: break-word;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-success {
        background: #dcfce7;
        color: #166534;
    }

    .status-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
        margin-bottom: 22px;
    }

    .table-card h3 {
        font-size: 18px;
        margin-bottom: 16px;
        color: #111827;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
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
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .section-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Source Tables</div>
            <div class="stat-value">{{ $stats['source_tables'] ?? 0 }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Destination Tables</div>
            <div class="stat-value">{{ $stats['destination_tables'] ?? 0 }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Admin Tables</div>
            <div class="stat-value">{{ $stats['admin_tables'] ?? 0 }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Healthy Connections</div>
            <div class="stat-value">{{ $stats['healthy_connections'] ?? 0 }}</div>
        </div>
    </div>

 <div class="section-grid">
    <div class="panel-card">
        <h3>Source Supabase Tables</h3>
        @if(!empty($sourceStatus['tables']))
            <div class="detail-list">
                @foreach($sourceStatus['tables'] as $table)
                    <div class="detail-row">
                        <span class="detail-label">{{ $loop->iteration }}</span>
                        <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-box">No source tables found.</div>
        @endif
    </div>

    <div class="panel-card">
        <h3>Destination Tables</h3>
        @if(!empty($destinationStatus['tables']))
            <div class="detail-list">
                @foreach($destinationStatus['tables'] as $table)
                    <div class="detail-row">
                        <span class="detail-label">{{ $loop->iteration }}</span>
                        <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-box">No destination tables found.</div>
        @endif
    </div>

    <div class="panel-card">
        <h3>Admin Database Tables</h3>
        @if(!empty($adminStatus['tables']))
            <div class="detail-list">
                @foreach($adminStatus['tables'] as $table)
                    <div class="detail-row">
                        <span class="detail-label">{{ $loop->iteration }}</span>
                        <span class="detail-value" style="text-align:left;">{{ $table }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-box">No admin tables found.</div>
        @endif
    </div>
</div>

    <div class="table-card">
        <h3>Recent Sync Runs</h3>

        @if(!empty($recentSyncRuns))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Module</th>
                            <th>Run Type</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Ended At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSyncRuns as $row)
                            <tr>
                                <td>{{ $row->id ?? '-' }}</td>
                                <td>{{ $row->module_name ?? '-' }}</td>
                                <td>{{ $row->run_type ?? '-' }}</td>
                                <td>{{ $row->status ?? '-' }}</td>
                                <td>{{ $row->started_at ?? '-' }}</td>
                                <td>{{ $row->ended_at ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No sync run data found.</div>
        @endif
    </div>

    <div class="table-card">
        <h3>Recent Health Checks</h3>

        @if(!empty($recentHealthChecks))
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
            <div class="empty-box">No health check data found.</div>
        @endif
    </div>

    <div class="table-card">
        <h3>Recent Backups</h3>

        @if(!empty($recentBackups))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Backup Type</th>
                            <th>File Name</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Ended At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBackups as $row)
                            <tr>
                                <td>{{ $row->id ?? '-' }}</td>
                                <td>{{ $row->backup_type ?? '-' }}</td>
                                <td>{{ $row->file_name ?? '-' }}</td>
                                <td>{{ $row->status ?? '-' }}</td>
                                <td>{{ $row->started_at ?? '-' }}</td>
                                <td>{{ $row->ended_at ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No backup history found.</div>
        @endif
    </div>

    <div class="table-card">
        <h3>Recent Audit Logs</h3>

        @if(!empty($recentAuditLogs))
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Target Type</th>
                            <th>Target ID</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentAuditLogs as $row)
                            <tr>
                                <td>{{ $row->id ?? '-' }}</td>
                                <td>{{ $row->module_name ?? '-' }}</td>
                                <td>{{ $row->action_name ?? '-' }}</td>
                                <td>{{ $row->target_type ?? '-' }}</td>
                                <td>{{ $row->target_id ?? '-' }}</td>
                                <td>{{ $row->created_at ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-box">No audit log records found.</div>
        @endif
    </div>
@endsection