<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'user_id',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];
}