<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Enquiry;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;
use App\Repositories\Interfaces\ServiceRepositoryInterface;
use App\Support\Enums\CustomerType;
use App\Support\Enums\EnquiryStatus;
use App\Support\Enums\InspectionType;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\TimelineActorType;
use App\Support\Enums\TimelineStatus;
use Illuminate\Support\Facades\DB;

class EnquiryService
{
    public function __construct(
        private readonly ServiceRepositoryInterface         $serviceRepository,
        private readonly EnquiryRepositoryInterface         $enquiryRepository,
        private readonly ProjectTimelineRepositoryInterface $timelineRepository,
    ) {}

    public function book(int $customerId, string $actorName, array $data): Enquiry
    {
        // Phase 1 gate — self inspection is reserved for Cycle 2
        if ($data['inspection_type'] === InspectionType::Self->value) {
            throw new BusinessRuleException('Self inspection is not available in this phase.');
        }

        $service = $this->serviceRepository->findActiveById($data['service_id']);

        if (! $service) {
            throw new BusinessRuleException('The selected service is not available.');
        }

        return DB::transaction(function () use ($customerId, $actorName, $data, $service) {
            // Step 1 — Lock the counter row, then increment; never use MAX() or COUNT()
            // lockForUpdate() is a SELECT-level hint — SELECT first, then UPDATE separately
            $row = DB::table('enquiry_counter')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            $counter = $row->last_value + 1;

            DB::table('enquiry_counter')
                ->where('id', 1)
                ->update(['last_value' => $counter, 'updated_at' => now()]);
            $enquiryNumber = sprintf('FMW-%s-%04d', now('UTC')->format('Ymd'), $counter);

            // Step 2 — Create enquiry; material_type is denormalised from the service
            $enquiry = $this->enquiryRepository->create([
                'enquiry_number'  => $enquiryNumber,
                'customer_id'     => $customerId,
                'service_id'      => $service->id,
                'type'            => CustomerType::B2C->value,
                'city'            => $data['location']['city'],
                'property_type'   => $data['property_type'],
                'material_type'   => $service->material->value,
                'inspection_type' => $data['inspection_type'],
                'status'          => EnquiryStatus::New->value,
                'latitude'        => $data['location']['latitude'],
                'longitude'       => $data['location']['longitude'],
                'address'         => $data['location']['address'],
                'inspection_fee'  => 1000,
                'payment_status'  => PaymentStatus::Pending->value,
                'billing_name'    => $data['billing']['name'],
                'billing_poc'     => $data['billing']['point_of_contact'] ?? null,
                'billing_gst'     => $data['billing']['gst_number'] ?? null,
                'billing_phone'   => $data['billing']['phone'],
                'billing_email'   => $data['billing']['email'],
                'billing_address' => $data['billing']['address'],
                'booking_date'    => today(),
            ]);

            // Step 3 — Mandatory timeline entry in the same transaction
            $this->timelineRepository->log([
                'enquiry_id'  => $enquiry->id,
                'project_id'  => null,
                'status'      => TimelineStatus::New->value,
                'description' => 'Booking created by customer.',
                'actor_type'  => TimelineActorType::Customer->value,
                'actor_id'    => $customerId,
                'actor_name'  => $actorName,
                'created_at'  => now(),
            ]);

            return $enquiry;
        });
    }
}
