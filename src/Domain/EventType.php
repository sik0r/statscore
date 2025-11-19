<?php

declare(strict_types=1);

namespace App\Domain;

enum EventType: string
{
    case Foul = 'foul';
    case Goal = 'goal';
}
