<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
        'towers',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'towers'      => 'array',
            'released_at' => 'datetime',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
