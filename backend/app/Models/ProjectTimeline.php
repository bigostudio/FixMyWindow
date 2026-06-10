<?php

namespace App\Models;

use App\Support\Enums\TimelineActorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimeline extends Model
{
    protected $table = 'project_timeline';

    // Append-only — no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'enquiry_id',
        'status',
        'description',
        'actor_type',
        'actor_id',
        'actor_name',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'actor_type' => TimelineActorType::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('project_timeline is append-only.'));
        static::deleting(fn () => throw new \LogicException('project_timeline is append-only.'));
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
