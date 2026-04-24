<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'module_name',
        'action_name',
        'description',
        'status',
        'admin_user_id',
        'admin_name',
        'ip_address',
        'user_agent',
        'context',
        'logged_at',
    ];

    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
    ];
}