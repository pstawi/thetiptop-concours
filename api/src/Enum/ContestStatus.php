<?php

namespace App\Enum;

enum ContestStatus: string
{
    case PREVIEW = 'PREVIEW';
    case OPEN = 'OPEN';
    case CLAIM = 'CLAIM';
    case CLOSED = 'CLOSED';
}
