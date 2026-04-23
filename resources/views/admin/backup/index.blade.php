@extends('admin.layouts.app')

@section('title', 'Backups - Bolt Sync Admin')
@section('page_title', 'Backups')
@section('page_subtitle', 'View backup health and recent backup activity')

@php
    $statusClass = function ($status) {
        return match($status) {
            'connected', 'success' => 'status-success',
            'failed', 'error' => 'status-danger',
            'running' => 'status-warning',
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
    .status-warning { background: #fef3c7; color: #92400e; }
    .status-dark { background: #e5e7eb; color: #111827; }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px;
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
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Backups</div>
        <div class="stat-value">{{ $stats['total_backups'] ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Successful</div>
        <div class="stat-value">{{ $stats['successful_backups'] ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value">{{ $stats['failed_backups'] ?? 0 }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Running</div>
        <div class="stat-value">{{ $stats['running_backups'] ?? 0 }}</div>
    </div>
</div>

<div class="section-grid">
    <div class="panel-card">
        <h3>Source Database</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $statusClass($sourceStatus['status'] ?? '') }}">{{ $sourceStatus['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $sourceStatus['database_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $sourceStatus['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tables</span>
                <span class="detail-value">{{ $sourceStatus['tables_count'] ?? 0 }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $sourceStatus['message'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <h3>Destination Database</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $statusClass($destinationStatus['status'] ?? '') }}">{{ $destinationStatus['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $destinationStatus['database_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $destinationStatus['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tables</span>
                <span class="detail-value">{{ $destinationStatus['tables_count'] ?? 0 }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $destinationStatus['message'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <h3>Admin Database</h3>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-chip {{ $statusClass($adminStatus['status'] ?? '') }}">{{ $adminStatus['status'] ?? '-' }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database</span>
                <span class="detail-value">{{ $adminStatus['database_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Schema</span>
                <span class="detail-value">{{ $adminStatus['schema_name'] ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tables</span>
                <span class="detail-value">{{ $adminStatus['tables_count'] ?? 0 }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Message</span>
                <span class="detail-value">{{ $adminStatus['message'] ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <h3>Recent Backup Runs</h3>

    @if(!empty($recentBackups) && count($recentBackups))
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Backup Type</th>
                        <th>Target</th>
                        <th>File Name</th>
                        <th>File Path</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Started At</th>
                        <th>Ended At</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBackups as $row)
                        <tr>
                            <td>{{ $row->id ?? '-' }}</td>
                            <td>{{ $row->backup_type ?? '-' }}</td>
                            <td>{{ $row->target_name ?? '-' }}</td>
                            <td>{{ $row->file_name ?? '-' }}</td>
                            <td>{{ $row->file_path ?? '-' }}</td>
                            <td>{{ $row->status ?? '-' }}</td>
                            <td>{{ $row->message ?? '-' }}</td>
                            <td>{{ $row->started_at ?? '-' }}</td>
                            <td>{{ $row->ended_at ?? '-' }}</td>
                            <td>{{ $row->created_by ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-box">No backup records found.</div>
    @endif
</div>
@endsection