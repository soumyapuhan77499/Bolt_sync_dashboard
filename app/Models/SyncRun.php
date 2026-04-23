<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $table = 'sync_runs';

    protected $fillable = [
        'module_name',
        'run_type',
        'sync_table_id',
        'source_table_name',
        'destination_table_name',
        'status',
        'records_processed',
        'started_at',
        'ended_at',
        'message',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}