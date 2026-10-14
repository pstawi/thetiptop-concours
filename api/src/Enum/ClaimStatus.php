<?php

namespace App\Enum;

enum ClaimStatus: string
{
    case PENDING = 'PENDING';
    case DELIVERED = 'DELIVERED';
    case EXPIRED = 'EXPIRED';
}
