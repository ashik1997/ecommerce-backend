<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleSidebarPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'permissions_json',
        'sidebar_json',
        'sidebar_hash',
        'mother_sidebar_hash',
        'cache_key',
        'updated_by',
    ];

    protected $casts = [
        'permissions_json' => 'array',
        'sidebar_json' => 'array',
    ];
}
