<?php

namespace App\Support\Enums;

enum ProjectStatus: string
{
    case OnTrack         = 'on_track';
    case AtRisk          = 'at_risk';
    case Delayed         = 'delayed';
    case DueToDependency = 'due_to_dependency';
    case Completed       = 'completed';
    case Cancelled       = 'cancelled';
}
