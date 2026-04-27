@extends('admin.layouts.app')

@section('title', 'Add Database Connection')
@section('page_title', 'Add Database Connection')
@section('page_subtitle', 'Create source or destination database connection')

@push('styles')
<style>
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
        max-width: 1100px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    label {
        font-size: 13px;
        font-weight: 800;
        color: #111827;
    }

    input,
    select,
    textarea {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 11px 13px;
        font-size: 14px;
        color: #111827;
        outline: none;
        background: #fff;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    textarea {
        min-height: 90px;
        resize: vertical;
    }

    .error-text {
        color: #dc2626;
        font-size: 12px;
        font-weight: 700;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
    }

    .btn {
        border: none;
        border-radius: 10px;
        padding: 11px 16px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-primary {
        background: #2563eb;
        color: #fff;
    }

    .btn-secondary {
        background: #111827;
        color: #fff;
    }

    .alert {
        padding: 13px 16px;
        border-radius: 14px;
        margin-bottom: 18px;
        font-size: 14px;
        font-weight: 700;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .hint {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.5;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        Please fix the validation errors below.
    </div>
@endif

<div class="form-card">
    <form method="POST" action="{{ route('admin.database-connections.store') }}">
        @csrf

        <div class="form-grid">

            <div class="form-group">
                <label>Connection Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Example: Source Supabase">
                @error('name') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Connection Type *</label>
                <select name="connection_type">
                    <option value="">Select Type</option>
                    <option value="source" {{ old('connection_type') == 'source' ? 'selected' : '' }}>Source</option>
                    <option value="destination" {{ old('connection_type') == 'destination' ? 'selected' : '' }}>Destination</option>
                </select>
                @error('connection_type') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Driver *</label>
                <select name="driver">
                    <option value="pgsql" {{ old('driver', 'pgsql') == 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
                    <option value="mysql" {{ old('driver') == 'mysql' ? 'selected' : '' }}>MySQL</option>
                </select>
                @error('driver') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Host *</label>
                <input type="text" name="host" value="{{ old('host') }}" placeholder="db.xxxxx.supabase.co or 127.0.0.1">
                @error('host') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Port *</label>
                <input type="number" name="port" value="{{ old('port', 5432) }}" placeholder="5432">
                @error('port') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Database Name *</label>
                <input type="text" name="database_name" value="{{ old('database_name', 'postgres') }}" placeholder="postgres">
                @error('database_name') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" value="{{ old('username', 'postgres') }}" placeholder="postgres">
                @error('username') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="{{ old('password') }}" placeholder="Database password">
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Schema</label>
                <input type="text" name="schema_name" value="{{ old('schema_name', 'public') }}" placeholder="public">
                @error('schema_name') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>SSL Mode *</label>
                <select name="sslmode">
                    <option value="disable" {{ old('sslmode', 'disable') == 'disable' ? 'selected' : '' }}>disable</option>
                    <option value="require" {{ old('sslmode') == 'require' ? 'selected' : '' }}>require</option>
                    <option value="prefer" {{ old('sslmode') == 'prefer' ? 'selected' : '' }}>prefer</option>
                    <option value="allow" {{ old('sslmode') == 'allow' ? 'selected' : '' }}>allow</option>
                    <option value="verify-ca" {{ old('sslmode') == 'verify-ca' ? 'selected' : '' }}>verify-ca</option>
                    <option value="verify-full" {{ old('sslmode') == 'verify-full' ? 'selected' : '' }}>verify-full</option>
                </select>
                @error('sslmode') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group full">
                <label>Supabase URL</label>
                <input type="url" name="supabase_url" value="{{ old('supabase_url') }}" placeholder="https://xxxxx.supabase.co">
                @error('supabase_url') <div class="error-text">{{ $message }}</div> @enderror
                <div class="hint">For normal PostgreSQL connection, this field is optional. It is useful for Supabase API operations.</div>
            </div>

            <div class="form-group full">
                <label>Supabase Anon Key</label>
                <textarea name="supabase_anon_key" placeholder="Paste anon key if needed">{{ old('supabase_anon_key') }}</textarea>
                @error('supabase_anon_key') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status">
                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group full">
                <label>Notes</label>
                <textarea name="notes" placeholder="Connection note">{{ old('notes') }}</textarea>
                @error('notes') <div class="error-text">{{ $message }}</div> @enderror
            </div>

        </div>

        <div class="form-actions">
            <a href="{{ route('admin.database-connections.index') }}" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary">Save Connection</button>
        </div>
    </form>
</div>

@endsection