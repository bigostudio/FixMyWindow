<?php

namespace App\Support\Enums;

use Illuminate\Support\Facades\DB;

enum ProjectStatus: string
{
    case New                   = 'new';
    case SurveyInitiated       = 'survey_initiated';
    case SurveyCompleted       = 'survey_completed';
    case MeasurementInitiated  = 'measurement_initiated';
    case MeasurementCompleted  = 'measurement_completed';
    case QuotationGenerated    = 'quotation_generated';
    case QuotationApproved     = 'quotation_approved';
    case InstallationInitiated = 'installation_initiated';
    case InstallationCompleted = 'installation_completed';
    case Handovered            = 'handovered';
    case OnHold                = 'on_hold';
    case Cancelled             = 'cancelled';

    public function label(): string
    {
        // Loaded once per request via static variable — no Redis or file cache needed.
        static $labels = null;

        if ($labels === null) {
            $labels = DB::table('project_statuses')->pluck('label', 'code')->all();
        }

        return $labels[$this->value] ?? $this->value;
    }
}
