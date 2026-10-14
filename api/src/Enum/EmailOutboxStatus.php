<?php

namespace App\Enum;

enum EmailOutboxStatus: string
{
    case PENDING = 'PENDING';
    case SENT = 'SENT';
    case FAILED = 'FAILED';
}
