<?php

namespace App\Support\Enums;

enum EnquiryStatus: string
{
    case New        = 'new';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
}
