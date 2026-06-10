<?php

namespace App\Support\Enums;

enum TimelineStatus: string
{
    case New                  = 'New';
    case SurveyorAssigned     = 'Surveyor Assigned';
    case SurveyPassed         = 'Survey Passed';
    case SurveyRejected       = 'Survey Rejected';
    case InspectionScheduled  = 'Inspection Scheduled';
    case InspectionCompleted  = 'Inspection Completed';
    case QuotationGenerated   = 'Quotation Generated';
    case QuotationApproved    = 'Quotation Approved';
    case WorkOrderCreated     = 'Work Order Created';
    case TeamAssigned         = 'Team Assigned';
    case WorkInProgress       = 'Work In Progress';
    case OnTrack              = 'On Track';
    case AtRisk               = 'At Risk';
    case Delayed              = 'Delayed';
    case DueToDependency      = 'Due to Dependency';
    case SnagRaised           = 'Snag Raised';
    case SnagClosed           = 'Snag Closed';
    case QcApproved           = 'QC Approved';
    case PaymentReceived      = 'Payment Received';
    case Completed            = 'Completed';
    case Cancelled            = 'Cancelled';
}
