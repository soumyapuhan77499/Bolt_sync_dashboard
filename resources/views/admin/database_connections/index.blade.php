@extends('admin.layouts.app')

@section('title', 'Database Connections')
@section('page_title', 'Database Connections')
@section('page_subtitle', 'Manage multiple database connections from one dashboard')

@push('styles')
<style>
    .page-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 18px;
    }

    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .table-head h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: #111827;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1350px;
    }

    .data-table th,
    .data-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        font-size: 13px;
        vertical-align: top;
        color: #111827;
    }

    .data-table th {
        background: #f9fafb;
        font-weight: 800;
        white-space: nowrap;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        display: inline-block;
        white-space: nowrap;
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
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        line-height: 1;
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

    .btn-secondary {
        background: #111827;
        color: #fff;
    }

    .action-form {
        display: inline-block;
        margin: 2px;
    }

    .alert {
        padding: 13px 16px;
        border-radius: 14px;
        margin-bottom: 18px;
        font-size: 14px;
        font-weight: 700;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .text-muted {
        color: #6b7280;
        font-size: 12px;
    }
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<div class="page-actions">
    <a href="{{ route('admin.database-connections.create') }}" class="btn btn-primary">
        + Add Database Connection
    </a>
</div>

<div class="table-card">
    <div class="table-head">
        <h3>All Database Connections</h3>
    </div>

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
                    <th>SSL Mode</th>
                    <th>Supabase URL</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th width="260">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($connections as $row)
                    <tr>
                        <td>{{ $row->id }}</td>

                        <td>
                            <strong>{{ $row->name }}</strong>
                            @if($row->notes)
                                <div class="text-muted">{{ Str::limit($row->notes, 45) }}</div>
                            @endif
                        </td>

                        <td>{{ ucfirst($row->connection_type) }}</td>
                        <td>{{ $row->driver }}</td>
                        <td>{{ $row->host }}</td>
                        <td>{{ $row->port }}</td>
                        <td>{{ $row->database_name }}</td>
                        <td>{{ $row->username }}</td>
                        <td>{{ $row->schema_name }}</td>
                        <td>{{ $row->sslmode }}</td>

                        <td>
                            @if($row->supabase_url)
                                <span class="text-muted">{{ $row->supabase_url }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>

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
                            <form class="action-form" method="POST" action="{{ route('admin.database-connections.test', $row->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning">Test</button>
                            </form>

                            @if(!$row->is_active)
                                <form class="action-form" method="POST" action="{{ route('admin.database-connections.activate', $row->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Activate</button>
                                </form>
                            @endif

                            @if($row->is_active)
                                <form class="action-form" method="POST" action="{{ route('admin.database-connections.deactivate', $row->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">Deactivate</button>
                                </form>
                            @endif

                            @if(!$row->is_active)
                                <form class="action-form" method="POST" action="{{ route('admin.database-connections.destroy', $row->id) }}" onsubmit="return confirm('Are you sure you want to delete this connection?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14">No database connections found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection