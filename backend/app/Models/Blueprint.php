<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blueprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'society_name',
        'tower_count',
        'floors_per_tower',
        'flats_per_floor',
        'include_ground_floor',
        'parking_floors',
        'office_floors',
        'towers',
    ];

    protected function casts(): array
    {
        return [
            'include_ground_floor' => 'boolean',
            'towers'               => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
