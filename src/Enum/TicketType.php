<?php

namespace App\Enum;

enum TicketType: string
{
    case DEMANDE = 'demande';
    case INCIDENT = 'incident';
}
