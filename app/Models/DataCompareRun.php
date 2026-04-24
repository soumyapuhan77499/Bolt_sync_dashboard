<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataCompareRun extends Model
{
    protected $table = 'data_compare_runs';

    protected $fillable = [
        'table_name',
        'primary_key_column',
        'compared_columns',
        'row_limit',
        'source_total_rows',
        'destination_total_rows',
        'source_loaded_rows',
        'destination_loaded_rows',
        'only_in_source_count',
        'only_in_destination_count',
        'changed_rows_count',
        'same_rows_count',
        'status',
        'message',
        'summary',
        'started_at',
        'ended_at',
        'created_by',
    ];

    protected $casts = [
        'compared_columns' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}