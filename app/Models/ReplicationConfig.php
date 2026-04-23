<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplicationConfig extends Model
{
    protected $table = 'replication_configs';

    protected $fillable = [
        'name',
        'source_connection_type',
        'destination_connection_type',
        'publication_name',
        'subscription_name',
        'replication_mode',
        'source_schema_name',
        'destination_schema_name',
        'source_tables',
        'sync_inserts',
        'sync_updates',
        'sync_deletes',
        'status',
        'last_message',
        'notes',
        'started_at',
        'stopped_at',
        'last_checked_at',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'sync_inserts' => 'boolean',
        'sync_updates' => 'boolean',
        'sync_deletes' => 'boolean',
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];
}