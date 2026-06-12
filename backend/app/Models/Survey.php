<?php

namespace App\Models;

use App\Support\Enums\SurveyOutcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
        'surveyor_id',
        'outcome',
        'client_details',
        'gonogo_matrix',
        'feasibility',
        'opening_data',
        'risk_register',
    ];

    protected function casts(): array
    {
        return [
            'outcome'        => SurveyOutcome::class,
            'client_details' => 'array',
            'gonogo_matrix'  => 'array',
            'feasibility'    => 'array',
            'opening_data'   => 'array',
            'risk_register'  => 'array',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }
}
