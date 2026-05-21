<?php

namespace App\Enums;

enum paymentStatus:string
{
    case PAID = 'paid';
    case FAILED = 'failed';
    case PENDING = 'pending';
    case REFUNDED = 'refunded';
}
