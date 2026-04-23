<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminUser extends Model
{
    use HasFactory;

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'user_id',
        'email',
        'password',
        'status',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
    ];
}