@extends('admin.layouts.app')

@section('title', 'Connections - Bolt Sync Admin')
@section('page_title', 'Connections')
@section('page_subtitle', 'Manage and test source and destination database connections')

@push('styles')
<style>
    .connection-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
    }

    .connection-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    .card-head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 12px;
        margin-bottom: 20px;
    }

    .card-head h3 {
        font-size: 22px;
        color: #111827;
        margin-bottom: 6px;
    }

    .card-head p {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.6;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
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

    .meta-box {
        margin-bottom: 18px;
        border: 1px dashed #d1d5db;
        background: #f9fafb;
        border-radius: 14px;
        padding: 14px;
        font-size: 13px;
    }

    .meta-box strong {
        color: #111827;
    }

    .meta-row {
        margin-bottom: 8px;
        color: #4b5563;
    }

    .meta-row:last-child {
        margin-bottom: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-control,
    .form-select {
        width: 100%;
        min-height: 48px;
        border: 1px solid #d1d5db;
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 14px;
        outline: none;
        transition: all 0.2s ease;
        background: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .hint-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
        line-height: 1.5;
    }

    .checkbox-row {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        border: 1px solid #d1d5db;
        border-radius: 14px;
        padding: 12px 14px;
        background: #fff;
    }

    .action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
    }

    .btn {
        border: none;
        border-radius: 14px;
        padding: 13px 18px;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: #2563eb;
        color: #fff;
    }

    .btn-primary:hover {
        background: #1d4ed8;
    }

    .btn-dark {
        background: #111827;
        color: #fff;
    }

    .btn-dark:hover {
        background: #030712;
    }

    @media (max-width: 1199px) {
        .connection-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    @php
        $badgeClass = function ($status) {
            return match($status) {
                'connected', 'success' => 'status-success',
                'failed', 'error' => 'status-danger',
                default => 'status-warning',
            };
        };
    @endphp

    <div class="connection-grid">
        <div class="connection-card">
            <div class="card-head">
                <div>
                    <h3>Source Supabase</h3>
                    <p>Configure your source cloud Supabase PostgreSQL connection.</p>
                </div>

                <span class="status-badge {{ $badgeClass($sourceConnection['last_test_status'] ?? null) }}">
                    {{ $sourceConnection['last_test_status'] ?? 'not tested' }}
                </span>
            </div>

            <div class="meta-box">
                <div class="meta-row">
                    <strong>Last Test Message:</strong>
                    {{ $sourceConnection['last_test_message'] ?? 'No test performed yet.' }}
                </div>
                <div class="meta-row">
                    <strong>Last Tested At:</strong>
                    {{ $sourceConnection['last_tested_at'] ?? '-' }}
                </div>
            </div>

            <form method="POST">
                @csrf
                <input type="hidden" name="connection_type" value="source">

                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Connection Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $sourceConnection['name']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Host</label>
                        <input type="text" name="host" class="form-control" value="{{ old('host', $sourceConnection['host']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" name="port" class="form-control" value="{{ old('port', $sourceConnection['port']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="database_name" class="form-control" value="{{ old('database_name', $sourceConnection['database_name']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username', $sourceConnection['username']) }}" required>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password only when updating or testing">
                        <div class="hint-text">For security, saved passwords are not shown here.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Schema Name</label>
                        <input type="text" name="schema_name" class="form-control" value="{{ old('schema_name', $sourceConnection['schema_name']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">SSL Mode</label>
                        <select name="sslmode" class="form-select" required>
                            @php $sourceSsl = old('sslmode', $sourceConnection['sslmode']); @endphp
                            <option value="disable" {{ $sourceSsl === 'disable' ? 'selected' : '' }}>disable</option>
                            <option value="allow" {{ $sourceSsl === 'allow' ? 'selected' : '' }}>allow</option>
                            <option value="prefer" {{ $sourceSsl === 'prefer' ? 'selected' : '' }}>prefer</option>
                            <option value="require" {{ $sourceSsl === 'require' ? 'selected' : '' }}>require</option>
                            <option value="verify-ca" {{ $sourceSsl === 'verify-ca' ? 'selected' : '' }}>verify-ca</option>
                            <option value="verify-full" {{ $sourceSsl === 'verify-full' ? 'selected' : '' }}>verify-full</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Active Status</label>
                        <div class="checkbox-row">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $sourceConnection['is_active']) ? 'checked' : '' }}>
                            <span>Enable this source connection</span>
                        </div>
                    </div>
                </div>

                <div class="action-row">
                    <button
                        type="submit"
                        class="btn btn-dark"
                        formaction="{{ route('admin.connections.test-source') }}"
                        formmethod="POST"
                    >
                        Test Source Connection
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        formaction="{{ route('admin.connections.save') }}"
                        formmethod="POST"
                    >
                        Save Source Connection
                    </button>
                </div>
            </form>
        </div>

        <div class="connection-card">
            <div class="card-head">
                <div>
                    <h3>Destination Database</h3>
                    <p>Configure your DigitalOcean or self-hosted PostgreSQL destination connection.</p>
                </div>

                <span class="status-badge {{ $badgeClass($destinationConnection['last_test_status'] ?? null) }}">
                    {{ $destinationConnection['last_test_status'] ?? 'not tested' }}
                </span>
            </div>

            <div class="meta-box">
                <div class="meta-row">
                    <strong>Last Test Message:</strong>
                    {{ $destinationConnection['last_test_message'] ?? 'No test performed yet.' }}
                </div>
                <div class="meta-row">
                    <strong>Last Tested At:</strong>
                    {{ $destinationConnection['last_tested_at'] ?? '-' }}
                </div>
            </div>

            <form method="POST">
                @csrf
                <input type="hidden" name="connection_type" value="destination">

                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Connection Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $destinationConnection['name']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Host</label>
                        <input type="text" name="host" class="form-control" value="{{ old('host', $destinationConnection['host']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" name="port" class="form-control" value="{{ old('port', $destinationConnection['port']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="database_name" class="form-control" value="{{ old('database_name', $destinationConnection['database_name']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username', $destinationConnection['username']) }}" required>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password only when updating or testing">
                        <div class="hint-text">For security, saved passwords are not shown here.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Schema Name</label>
                        <input type="text" name="schema_name" class="form-control" value="{{ old('schema_name', $destinationConnection['schema_name']) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">SSL Mode</label>
                        <select name="sslmode" class="form-select" required>
                            @php $destinationSsl = old('sslmode', $destinationConnection['sslmode']); @endphp
                            <option value="disable" {{ $destinationSsl === 'disable' ? 'selected' : '' }}>disable</option>
                            <option value="allow" {{ $destinationSsl === 'allow' ? 'selected' : '' }}>allow</option>
                            <option value="prefer" {{ $destinationSsl === 'prefer' ? 'selected' : '' }}>prefer</option>
                            <option value="require" {{ $destinationSsl === 'require' ? 'selected' : '' }}>require</option>
                            <option value="verify-ca" {{ $destinationSsl === 'verify-ca' ? 'selected' : '' }}>verify-ca</option>
                            <option value="verify-full" {{ $destinationSsl === 'verify-full' ? 'selected' : '' }}>verify-full</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Active Status</label>
                        <div class="checkbox-row">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $destinationConnection['is_active']) ? 'checked' : '' }}>
                            <span>Enable this destination connection</span>
                        </div>
                    </div>
                </div>

                <div class="action-row">
                    <button
                        type="submit"
                        class="btn btn-dark"
                        formaction="{{ route('admin.connections.test-destination') }}"
                        formmethod="POST"
                    >
                        Test Destination Connection
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        formaction="{{ route('admin.connections.save') }}"
                        formmethod="POST"
                    >
                        Save Destination Connection
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection