<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralInfo extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $hidden = [
        'fb_pixel_api_key',
        'fb_test_event_code',
        'tiktok_pixel_token',
        'tiktok_pixel_secret',
        'sms_api_key',
    ];
}
