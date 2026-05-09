<?php

namespace App\Enums;

enum HotelStatus:string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
