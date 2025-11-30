<?php

namespace App\Models;

use App\Enums\HoldStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    /** @use HasFactory<\Database\Factories\HoldFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'qty', 'expires_at', 'status'];

    protected $casts = [
        'expires_at' => 'datetime',
        'qty' => 'integer',
        'status' => HoldStatusEnum::class,
    ];

    public function isActive()
    {
        return $this->status === HoldStatusEnum::Active && $this->expires_at->isFuture();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
