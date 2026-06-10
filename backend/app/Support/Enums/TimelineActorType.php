<?php

namespace App\Support\Enums;

enum TimelineActorType: string
{
    case System         = 'system';
    case Admin          = 'admin';
    case OpsAdmin       = 'ops_admin';
    case ProjectManager = 'project_manager';
    case Surveyor       = 'surveyor';
    case Installer      = 'installer';
    case QcEngineer     = 'qc_engineer';
    case Customer       = 'customer';
}
