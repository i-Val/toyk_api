<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'product_type_id',
        'title',
        'description',
        'price',
        'status',
        'currency_code',
        'contact',
        'expiry',
        'lat',
        'lng',
        'country',
        'state',
        'city',
        'address',
        'zipcode',
        'total_views',
        'ad_type',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'is_featured' => 'boolean',
            'expiry' => 'date',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
