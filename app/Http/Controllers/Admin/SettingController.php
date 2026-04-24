<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SettingController extends Controller
{
    public function index()
    {
        $this->seedDefaultSettings();

        $settings = AppSetting::orderBy('setting_group')
            ->orderBy('id')
            ->get()
            ->groupBy('setting_group');

        return view('admin.settings.index', compact('settings'));
    }

    public function save(Request $request)
    {
        $this->seedDefaultSettings();

        $settings = AppSetting::where('is_editable', true)->get();

        foreach ($settings as $setting) {
            $fieldName = 'setting_' . $setting->setting_key;

            $value = match ($setting->input_type) {
                'checkbox' => $request->has($fieldName) ? '1' : '0',
                'textarea' => $request->input($fieldName),
                'number' => $request->input($fieldName),
                default => $request->input($fieldName),
            };

            AppSetting::where('id', $setting->id)->update([
                'setting_value' => $value,
                'updated_by' => session('admin_user_id'),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    private function seedDefaultSettings(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        $defaults = [
            [
                'setting_key' => 'app_name',
                'setting_label' => 'Application Name',
                'setting_group' => 'General',
                'setting_value' => 'Bolt Sync Admin',
                'input_type' => 'text',
                'is_editable' => true,
                'notes' => 'Display name of the admin panel.',
            ],
            [
                'setting_key' => 'default_source_schema',
                'setting_label' => 'Default Source Schema',
                'setting_group' => 'Database',
                'setting_value' => 'public',
                'input_type' => 'text',
                'is_editable' => true,
                'notes' => 'Default source schema name.',
            ],
            [
                'setting_key' => 'default_destination_schema',
                'setting_label' => 'Default Destination Schema',
                'setting_group' => 'Database',
                'setting_value' => 'public',
                'input_type' => 'text',
                'is_editable' => true,
                'notes' => 'Default destination schema name.',
            ],
            [
                'setting_key' => 'enable_delete_sync',
                'setting_label' => 'Enable Delete Sync',
                'setting_group' => 'Replication',
                'setting_value' => '0',
                'input_type' => 'checkbox',
                'is_editable' => true,
                'notes' => 'Allow delete operations during sync.',
            ],
            [
                'setting_key' => 'health_check_interval',
                'setting_label' => 'Health Check Interval (minutes)',
                'setting_group' => 'Monitoring',
                'setting_value' => '30',
                'input_type' => 'number',
                'is_editable' => true,
                'notes' => 'How often health checks should run.',
            ],
            [
                'setting_key' => 'backup_retention_days',
                'setting_label' => 'Backup Retention Days',
                'setting_group' => 'Backup',
                'setting_value' => '7',
                'input_type' => 'number',
                'is_editable' => true,
                'notes' => 'Number of days to keep backup records.',
            ],
        ];

        foreach ($defaults as $item) {
            AppSetting::updateOrCreate(
                ['setting_key' => $item['setting_key']],
                $item
            );
        }
    }
}