<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SplashMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'web_message',
        'web_link',
        'app_message',
        'messgae_bg_color',
    ];
}

