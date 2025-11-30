<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum OrderStatusEnum: string
{
    use EnumToArray;
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
}


