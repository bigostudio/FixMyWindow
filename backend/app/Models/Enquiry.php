<?php

namespace App\Models;

use App\Support\Enums\CustomerType;
use App\Support\Enums\EnquiryStatus;
use App\Support\Enums\InspectionType;
use App\Support\Enums\MaterialType;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\PropertyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_number',
        'customer_id',
        'service_id',
        'type',
        'city',
        'property_type',
        'material_type',
        'inspection_type',
        'status',
        'latitude',
        'longitude',
        'address',
        'inspection_fee',
        'payment_status',
        'payment_amount',
        'payment_ref',
        'receipt_url',
        'billing_name',
        'billing_poc',
        'billing_gst',
        'billing_phone',
        'billing_email',
        'billing_address',
        'blueprint_id',
        'quotation_id',
        'booking_date',
    ];

    protected function casts(): array
    {
        return [
            'type'            => CustomerType::class,
            'property_type'   => PropertyType::class,
            'material_type'   => MaterialType::class,
            'inspection_type' => InspectionType::class,
            'status'          => EnquiryStatus::class,
            'payment_status'  => PaymentStatus::class,
            'latitude'        => 'decimal:7',
            'longitude'       => 'decimal:7',
            'booking_date'    => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(ProjectTimeline::class)
                    ->orderBy('created_at');
    }
}
