<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PaymentStatusEnum: string
{
    use EnumToArray;
    case Success = 'success';
    case Failure = 'failure';
}


