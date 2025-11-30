<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentLogFactory> */
    use HasFactory;
    protected $fillable = ['order_id', 'status', 'gateway_payload', 'payment_reference'];

    protected $casts = [
        'status' => PaymentStatusEnum::class,
    ];
}
