<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncTable extends Model
{
    protected $table = 'sync_tables';

    protected $fillable = [
        'name',
        'transfer_direction',
        'source_table_name',
        'destination_table_name',
        'sync_mode',
        'primary_key_column',
        'selected_columns',
        'notes',
        'is_active',
        'last_synced_at',
        'last_sync_status',
        'last_sync_message',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}