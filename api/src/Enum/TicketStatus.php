<?php

namespace App\Enum;

enum TicketStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case USED = 'USED';
}
