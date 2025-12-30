<?php

namespace App\Enums;

enum RecordType: string
{
    case Fastest5K = 'fastest_5k';
    case Fastest10K = 'fastest_10k';
    case FastestHalfMarathon = 'fastest_half_marathon';
    case FastestMarathon = 'fastest_marathon';
    case LongestRun = 'longest_run';
    case FastestPace = 'fastest_pace';
}
