<?php

namespace App\Enums;

enum WorkoutStatus: string
{
    case Completed = 'completed';
    case Skipped = 'skipped';
    case PartiallyCompleted = 'partially_completed';
}
