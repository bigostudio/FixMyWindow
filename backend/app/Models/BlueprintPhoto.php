<?php

namespace App\Models;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintPhoto extends Model
{
    protected $fillable = [
        'enquiry_id',
        'file_path',
        'original_name',
        'uploaded_by_type',
        'uploaded_by_id',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
