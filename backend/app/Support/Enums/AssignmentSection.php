<?php

namespace App\Support\Enums;

enum AssignmentSection: string
{
    case OpsManager   = 'ops_manager';
    case Supervisor   = 'supervisor';
    case Survey       = 'survey';
    case Measurement  = 'measurement';
    case QualityCheck = 'quality_check';
    case Installation = 'installation';
}
