<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = ['qty', 'hold_id', 'status', 'expires_at'];

    protected $with = ['hold'];

    protected $casts = [
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => OrderStatusEnum::class,
    ];

    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }
}
