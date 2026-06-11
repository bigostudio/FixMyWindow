<?php

namespace App\Models;

use App\Support\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
        'status',
        'delay_reason',
        'total_units',
        'units_completed',
        'progress_percent',
        'project_details',
        'material_status',
        'quality_checks',
        'final_completion_date',
    ];

    protected function casts(): array
    {
        return [
            'status'               => ProjectStatus::class,
            'project_details'      => 'array',
            'material_status'      => 'array',
            'quality_checks'       => 'array',
            'final_completion_date' => 'date',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(ProjectTimeline::class)->orderBy('created_at');
    }
}
