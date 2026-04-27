@extends('admin.layouts.app')

@section('title', 'Database Connections')
@section('page_title', 'Database Connections')
@section('page_subtitle', 'Manage multiple database connections from one dashboard')

@push('styles')
<style>
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .table-card h3 {
        font-size: 20px;
        margin-bottom: 16px;
        color: #111827;
    }

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
        font-weight: 700;
        color: #374151;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-success {
        background: #dcfce7;
        color: #166534;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-dark {
        background: #e5e7eb;
        color: #111827;
    }

    .btn {
        border: none;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-primary {
        background: #2563eb;
        color: #fff;
    }

    .btn-warning {
        background: #f59e0b;
        color: #fff;
    }

    .btn-danger {
        background: #dc2626;
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

    .action-form {
        display: inline-block;
        margin-right: 5px;
        margin-bottom: 5px;
    }
</style>
@endpush

@section('content')
<div class="table-card">
    <h3>All Database Connections</h3>

    @if($connections->count())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Driver</th>
                        <th>Host</th>
                        <th>Port</th>
                        <th>Database</th>
                        <th>Username</th>
                        <th>Schema</th>
                        <th>Supabase URL</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($connections as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ ucfirst($row->connection_type) }}</td>
                            <td>{{ $row->driver }}</td>
                            <td>{{ $row->host }}</td>
                            <td>{{ $row->port }}</td>
                            <td>{{ $row->database_name }}</td>
                            <td>{{ $row->username }}</td>
                            <td>{{ $row->schema_name }}</td>
                            <td>{{ $row->supabase_url ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $row->status === 'active' ? 'badge-success' : 'badge-dark' }}">
                                    {{ strtoupper($row->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $row->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $row->is_active ? 'YES' : 'NO' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.database-connections.test', $row->id) }}" class="action-form">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">Test</button>
                                </form>

                                <form method="POST" action="{{ route('admin.database-connections.activate', $row->id) }}" class="action-form">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Activate</button>
                                </form>

                                <form method="POST" action="{{ route('admin.database-connections.deactivate', $row->id) }}" class="action-form">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">Deactivate</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-box">No database connections found.</div>
    @endif
</div>
@endsection