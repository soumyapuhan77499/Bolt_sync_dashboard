@extends('admin.layouts.app')

@section('title', 'Settings - Bolt Sync Admin')
@section('page_title', 'Settings')
@section('page_subtitle', 'Manage application configuration values')

@push('styles')
<style>
    .settings-group {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
        margin-bottom: 22px;
    }

    .settings-group h3 {
        font-size: 18px;
        margin-bottom: 16px;
        color: #111827;
    }

    .settings-grid {
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

    .check-wrap {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 46px;
        font-weight: 600;
        color: #374151;
    }

    .action-row {
        display: flex;
        gap: 12px;
        margin-top: 18px;
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
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('admin.settings.save') }}">
    @csrf

    @forelse($settings as $groupName => $groupSettings)
        <div class="settings-group">
            <h3>{{ $groupName ?: 'General' }}</h3>

            <div class="settings-grid">
                @foreach($groupSettings as $setting)
                    <div class="form-group {{ $setting->input_type === 'textarea' ? 'full' : '' }}">
                        <label class="form-label">{{ $setting->setting_label }}</label>

                        @if($setting->input_type === 'textarea')
                            <textarea
                                name="setting_{{ $setting->setting_key }}"
                                class="form-textarea"
                                {{ !$setting->is_editable ? 'readonly' : '' }}
                            >{{ old('setting_' . $setting->setting_key, $setting->setting_value) }}</textarea>

                        @elseif($setting->input_type === 'checkbox')
                            <label class="check-wrap">
                                <input
                                    type="checkbox"
                                    name="setting_{{ $setting->setting_key }}"
                                    value="1"
                                    {{ old('setting_' . $setting->setting_key, $setting->setting_value) == '1' ? 'checked' : '' }}
                                    {{ !$setting->is_editable ? 'disabled' : '' }}
                                >
                                Enabled
                            </label>

                        @else
                            <input
                                type="{{ $setting->input_type === 'number' ? 'number' : 'text' }}"
                                name="setting_{{ $setting->setting_key }}"
                                class="form-control"
                                value="{{ old('setting_' . $setting->setting_key, $setting->setting_value) }}"
                                {{ !$setting->is_editable ? 'readonly' : '' }}
                            >
                        @endif

                        @if(!empty($setting->notes))
                            <div class="form-note">{{ $setting->notes }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty-box">No settings found.</div>
    @endforelse

    <div class="action-row">
        <button type="submit" class="btn">Save Settings</button>
    </div>
</form>
@endsection