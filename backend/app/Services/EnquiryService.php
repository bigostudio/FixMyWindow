<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Repositories\Interfaces\BlueprintPhotoRepositoryInterface;
use App\Repositories\Interfaces\BlueprintRepositoryInterface;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;
use App\Repositories\Interfaces\ServiceRepositoryInterface;
use App\Support\Enums\CustomerType;
use App\Support\Enums\InspectionType;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TimelineActorType;
use App\Support\Enums\Role;
use App\Support\Enums\TimelineStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnquiryService
{
    public function __construct(
        private readonly ServiceRepositoryInterface         $serviceRepository,
        private readonly EnquiryRepositoryInterface         $enquiryRepository,
        private readonly ProjectTimelineRepositoryInterface $timelineRepository,
        private readonly BlueprintRepositoryInterface       $blueprintRepository,
        private readonly BlueprintPhotoRepositoryInterface  $blueprintPhotoRepository,
    ) {}

    // ── Customer ──────────────────────────────────────────────────────────

    public function create(Customer $customer, array $data): Enquiry
    {
        $service = $this->serviceRepository->findActiveById($data['service_id']);
        if (! $service) {
            throw new BusinessRuleException('The selected service is not available.');
        }

        $blueprintId = $data['blueprint_id'] ?? null;

        if ($blueprintId !== null) {
            $blueprint = $this->blueprintRepository->findById($blueprintId);
            if (! $blueprint || $blueprint->customer_id !== $customer->id) {
                throw new BusinessRuleException('Blueprint not found.');
            }
        }

        return DB::transaction(function () use ($customer, $data, $service, $blueprintId) {
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
                'customer_id'     => $customer->id,
                'service_id'      => $service->id,
                'type'            => $customer->type->value,
                'city'            => $data['location']['city'],
                'property_type'   => $data['property_type'],
                'material_type'   => $service->material->value,
                'inspection_type' => InspectionType::Expert->value,
                'status'          => ProjectStatus::New->value,
                'latitude'        => $data['location']['latitude'],
                'longitude'       => $data['location']['longitude'],
                'address'         => $data['location']['address'],
                'inspection_fee'  => $customer->type === CustomerType::B2C ? 1000 : 0,
                'payment_status'  => PaymentStatus::Paid->value,
                'billing_name'    => $data['billing']['name'],
                'billing_poc'     => $data['billing']['point_of_contact'] ?? null,
                'billing_gst'     => $data['billing']['gst_number'] ?? null,
                'billing_phone'   => $data['billing']['phone'],
                'billing_email'   => $data['billing']['email'] ?? null,
                'billing_address' => $data['billing']['address'],
                'booking_date'    => now(),
                'blueprint_id'    => $blueprintId,
            ]);

            $this->timelineRepository->log([
                'enquiry_id'  => $enquiry->id,
                'status'      => TimelineStatus::New->value,
                'description' => 'Booking created by customer.',
                'actor_type'  => TimelineActorType::Customer->value,
                'actor_id'    => $customer->id,
                'actor_name'  => $customer->name ?? 'Customer',
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

    public function listForAdmin(User $actor, int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        $sort  = in_array($sort, ['created_at', 'booking_date', 'status'], true) ? $sort : 'created_at';
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        $adminRoles = [Role::Admin, Role::OperationsManager];

        if (in_array($actor->role, $adminRoles, true)) {
            return $this->enquiryRepository->paginateAll($perPage, $sort, $order);
        }

        return $this->enquiryRepository->paginateForUser($actor->id, $perPage, $sort, $order);
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

    // ── Admin — link blueprint ────────────────────────────────────────────

    public function linkBlueprint(int $enquiryId, int $blueprintId): Enquiry
    {
        $enquiry = $this->enquiryRepository->findById($enquiryId);

        if (! $enquiry) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        if ($enquiry->blueprint_id !== null) {
            throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                'A blueprint is already linked to this enquiry.'
            );
        }

        return $this->enquiryRepository->update($enquiry, ['blueprint_id' => $blueprintId]);
    }

    // ── Admin — delete ────────────────────────────────────────────────────

    public function delete(int $enquiryId): void
    {
        $enquiry = $this->enquiryRepository->findById($enquiryId);

        if (! $enquiry) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        // blueprint_photos rows cascade-delete at the DB level, but the physical
        // files on the cloud disk don't — remove those first.
        foreach ($this->blueprintPhotoRepository->findByEnquiryId($enquiry->id) as $photo) {
            Storage::disk('cloud')->delete($photo->file_path);
        }

        if ($enquiry->receipt_url) {
            Storage::disk('cloud')->delete($enquiry->receipt_url);
        }

        // The linked blueprint is a reusable customer template (possibly shared
        // with other enquiries), so it is intentionally left untouched here.
        $this->enquiryRepository->delete($enquiry);
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
            ProjectStatus::QualityCheckInitiated => TimelineStatus::QualityCheckInitiated,
            ProjectStatus::QualityCheckCompleted => TimelineStatus::QualityCheckCompleted,
            ProjectStatus::Handovered            => TimelineStatus::Completed,
            ProjectStatus::Cancelled             => TimelineStatus::Cancelled,
            default                              => TimelineStatus::New,
        };
    }
}
