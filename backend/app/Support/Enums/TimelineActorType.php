<?php

namespace App\Support\Enums;

enum TimelineActorType: string
{
    case System            = 'system';
    case Admin             = 'ops_admin';
    case OperationsManager = 'ops_manager';
    case Supervisor        = 'supervisor';
    case Technician        = 'technician';
    case Customer          = 'customer';
}
