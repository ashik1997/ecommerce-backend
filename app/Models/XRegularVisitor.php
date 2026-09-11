<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XRegularVisitor extends Model
{
    use HasFactory;

    protected $table = 'x_regular_visitors';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'referrer',
        'url',
        'page',
        'device',
        'browser',
        'os',
        'country',
        'city',
        'latitude',
        'longitude',
        'isp',
        'org',
        'as',
        'as_name',
        'as_domain',
        'as_route',
        'as_type',
        'as_regional',
        'as_region',
        'as_city',
        'as_zip',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
