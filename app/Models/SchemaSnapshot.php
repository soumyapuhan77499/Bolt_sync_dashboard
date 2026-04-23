<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchemaSnapshot extends Model
{
    protected $table = 'schema_snapshots';

    protected $fillable = [
        'target_name',
        'database_name',
        'schema_name',
        'snapshot_data',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
    ];
}