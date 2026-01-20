<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'free_posts',
        'allow_sms',
        'email',
        'phone',
        'w_phone',
        'address',
        'facebook',
        'twitter',
        'instagram',
        'youtube',
        'linkdin',
        'app_version',
        'ios_version',
        'top_categories',
        'nav_categories',
    ];

    protected $casts = [
        'free_posts' => 'boolean',
        'allow_sms' => 'boolean',
        'top_categories' => 'array',
        'nav_categories' => 'array',
    ];
}

