<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseConnection extends Model
{
    protected $table = 'database_connections';

    protected $fillable = [
        'name',
        'connection_type',
        'driver',
        'host',
        'port',
        'database_name',
        'username',
        'password',
        'schema_name',
        'sslmode',
        'supabase_url',
        'supabase_anon_key',
        'is_active',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}