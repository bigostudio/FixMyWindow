<?php

namespace App\Support\Enums;

enum InspectionType: string
{
    case Expert = 'expert';
    case Self   = 'self';   // Cycle 2 — schema exists, gated in Phase 1
}
