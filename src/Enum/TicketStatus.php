<?php

namespace App\Enum;

enum TicketStatus: string
{
    case OPENED = 'OPENED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case PENDING = 'PENDING';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
}
