<?php

namespace App\Support\Enums;

enum SurveyOutcome: string
{
    case Go   = 'go';
    case Hold = 'hold';
    case NoGo = 'no_go';
}
