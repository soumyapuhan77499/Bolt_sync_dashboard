<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'setting_key',
        'setting_label',
        'setting_group',
        'setting_value',
        'input_type',
        'is_editable',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
    ];
}