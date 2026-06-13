<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Enquiry;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;
use App\Repositories\Interfaces\ServiceRepositoryInterface;
use App\Support\Enums\CustomerType;
use App\Support\Enums\InspectionType;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TimelineActorType;
use App\Support\Enums\TimelineStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnquiryService
{
    public function __construct(
        private readonly ServiceRepositoryInterface         $serviceRepository,
        private readonly EnquiryRepositoryInterface         $enquiryRepository,
        private readonly ProjectTimelineRepositoryInterface $timelineRepository,
    ) {}

    // ── Customer ──────────────────────────────────────────────────────────

    public function book(int $customerId, string $actorName, array $data): Enquiry
    {
        if ($data['inspection_type'] === InspectionType::Self->value) {
            throw new BusinessRuleException('Self inspection is not available in this phase.');
        }

        $service = $this->serviceRepository->findActiveById($data['service_id']);
        if (! $service) {
            throw new BusinessRuleException('The selected service is not available.');
        }

        return DB::transaction(function () use ($customerId, $actorName, $data, $service) {
            $row = DB::table('enquiry_counter')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            $counter = $row->last_value + 1;
            DB::table('enquiry_counter')
                ->where('id', 1)
                ->update(['last_value' => $counter, 'updated_at' => now()]);

            $enquiryNumber = sprintf('FMW-%s-%04d', now('UTC')->format('Ymd'), $counter);

            $enquiry = $this->enquiryRepository->create([
                'enquiry_number'  => $enquiryNumber,
                'customer_id'     => $customerId,
                'service_id'      => $service->id,
                'type'            => CustomerType::B2C->value,
                'city'            => $data['location']['city'],
                'property_type'   => $data['property_type'],
                'material_type'   => $service->material->value,
                'inspection_type' => $data['inspection_type'],
                'status'          => ProjectStatus::New->value,
                'latitude'        => $data['location']['latitude'],
                'longitude'       => $data['location']['longitude'],
                'address'         => $data['location']['address'],
                'inspection_fee'  => 1000,
                'payment_status'  => PaymentStatus::Paid->value,
                'billing_name'    => $data['billing']['name'],
                'billing_poc'     => $data['billing']['point_of_contact'] ?? null,
                'billing_gst'     => $data['billing']['gst_number'] ?? null,
                'billing_phone'   => $data['billing']['phone'],
                'billing_email'   => $data['billing']['email'],
                'billing_address' => $data['billing']['address'],
                'booking_date'    => today(),
            ]);

            $this->timelineRepository->log([
                'enquiry_id'  => $enquiry->id,
                'status'      => TimelineStatus::New->value,
                'description' => 'Booking created by customer. Expert inspection fee of Rs. 1000 paid.',
                'actor_type'  => TimelineActorType::Customer->value,
                'actor_id'    => $customerId,
                'actor_name'  => $actorName,
                'created_at'  => now(),
            ]);

            $this->timelineRepository->log([
                'enquiry_id'  => $enquiry->id,
                'status'      => TimelineStatus::PaymentReceived->value,
                'description' => 'Inspection fee of Rs. 1000 received.',
                'actor_type'  => TimelineActorType::System->value,
                'actor_id'    => null,
                'actor_name'  => 'System',
                'created_at'  => now(),
            ]);

            return $enquiry;
        });
    }

    public function listForCustomer(int $customerId, int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        $sort  = in_array($sort, ['created_at', 'booking_date', 'status'], true) ? $sort : 'created_at';
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        return $this->enquiryRepository->paginateByCustomer($customerId, $perPage, $sort, $order);
    }

    public function getForCustomer(int $enquiryId, int $customerId): Enquiry
    {
        $enquiry = $this->enquiryRepository->findById($enquiryId);

        if (! $enquiry || $enquiry->customer_id !== $customerId) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return $enquiry;
    }

    public function getAddressesForCustomer(int $customerId): \Illuminate\Support\Collection
    {
        return $this->enquiryRepository->getDistinctAddressesByCustomer($customerId);
    }

    // ── Admin — list / show ───────────────────────────────────────────────

    public function listForAdmin(int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        $sort  = in_array($sort, ['created_at', 'booking_date', 'status'], true) ? $sort : 'created_at';
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        return $this->enquiryRepository->paginateAll($perPage, $sort, $order);
    }

    public function getById(int $enquiryId): Enquiry
    {
        $enquiry = $this->enquiryRepository->findByIdWithRelations($enquiryId);

        if (! $enquiry) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return $enquiry;
    }

    // ── Admin — status update ─────────────────────────────────────────────

    public function updateStatus(int $enquiryId, User $actor, array $data): Enquiry
    {
        $enquiry = $this->enquiryRepository->findById($enquiryId);

        if (! $enquiry) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $statusEnum     = ProjectStatus::from($data['status']);
        $timelineStatus = $this->timelineStatusFor($statusEnum);

        return DB::transaction(function () use ($enquiry, $statusEnum, $timelineStatus, $actor) {
            $enquiry = $this->enquiryRepository->update($enquiry, ['status' => $statusEnum->value]);

            $this->timelineRepository->log([
                'enquiry_id'  => $enquiry->id,
                'status'      => $timelineStatus->value,
                'description' => 'Status updated to ' . $statusEnum->label() . '.',
                'actor_type'  => $actor->role->value,
                'actor_id'    => $actor->id,
                'actor_name'  => $actor->name,
            ]);

            return $enquiry;
        });
    }

    // ── Admin — statuses lookup ───────────────────────────────────────────

    public function getStatuses(): Collection
    {
        return DB::table('project_statuses')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['code', 'label', 'sort_order']);
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function timelineStatusFor(ProjectStatus $status): TimelineStatus
    {
        return match ($status) {
            ProjectStatus::SurveyInitiated       => TimelineStatus::InspectionScheduled,
            ProjectStatus::SurveyCompleted       => TimelineStatus::SurveyPassed,
            ProjectStatus::InstallationInitiated => TimelineStatus::WorkInProgress,
            ProjectStatus::InstallationCompleted => TimelineStatus::WorkInProgress,
            ProjectStatus::Handovered            => TimelineStatus::Completed,
            ProjectStatus::Cancelled             => TimelineStatus::Cancelled,
            default                              => TimelineStatus::New,
        };
    }
}
