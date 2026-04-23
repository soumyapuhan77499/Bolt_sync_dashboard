<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    protected $table = 'backup_runs';

    protected $fillable = [
        'backup_type',
        'target_name',
        'file_name',
        'file_path',
        'status',
        'message',
        'started_at',
        'ended_at',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}