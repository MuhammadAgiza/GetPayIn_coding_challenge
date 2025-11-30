<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum HoldStatusEnum: string
{
    use EnumToArray;
    case Active = 'active';
    case Expired = 'expired';
    case Used = 'used';
    case Cancelled = 'Cancelled';

}


