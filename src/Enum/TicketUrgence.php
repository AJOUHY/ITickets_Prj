<?php

namespace App\Enum;

enum TicketUrgence: string
{
    case BASSE = 'basse';
    case MOYENNE = 'moyenne';
    case HAUTE = 'haute';
    case TRES_HAUTE = 'très haute';
}
