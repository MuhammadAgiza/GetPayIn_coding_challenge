<?php

namespace App\Models;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = ['name', 'price_in_cents', 'stock'];
    protected $casts = [
        'price' => 'float'
    ];

    public function getPriceAttribute(): float
    {
        return (float) $this->attributes['price_in_cents'] / 100;
    }

    public function setPriceAttribute($val)
    {
        $this->attributes['price_in_cents'] = $val * 100;
    }

    public function holds()
    {
        return $this->hasMany(Hold::class);
    }
}
