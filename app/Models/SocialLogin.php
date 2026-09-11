<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLogin extends Model
{
    use HasFactory;

    protected $hidden = [
        'fb_app_secret',
        'gmail_secret_id',
    ];
}
