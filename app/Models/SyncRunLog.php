<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRunLog extends Model
{
    protected $table = 'sync_run_logs';

    protected $fillable = [
        'sync_run_id',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}