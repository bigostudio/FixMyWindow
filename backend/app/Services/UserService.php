<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Mail\AdminApprovedMail;
use App\Mail\AdminRejectedMail;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Support\Enums\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getPending(int $page, int $limit): LengthAwarePaginator
    {
        return $this->userRepository->getPending($page, $limit);
    }

    public function getActiveStaff(): Collection
    {
        return $this->userRepository->getByRoles([
            Role::Admin->value,
            Role::OperationsManager->value,
            Role::Supervisor->value,
            Role::Technician->value,
        ]);
    }

    public function create(array $data): User
    {
        if ($this->userRepository->emailExists($data['email'])) {
            throw new ConflictException('A user with this email address already exists.');
        }

        return $this->userRepository->create(array_merge($data, [
            'is_active'         => true,
            'email_verified_at' => now(),
        ]));
    }

    public function approve(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new BusinessRuleException('User not found.');
        }

        if ($user->is_active) {
            throw new BusinessRuleException('User is already active.');
        }

        $user->update(['is_active' => true]);

        Mail::to($user->email)->send(new AdminApprovedMail($user->name));

        return $user->fresh();
    }

    public function reject(int $id): void
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new BusinessRuleException('User not found.');
        }

        if ($user->is_active) {
            throw new BusinessRuleException('Cannot reject an already active user.');
        }

        Mail::to($user->email)->send(new AdminRejectedMail($user->name));

        $this->userRepository->delete($user);
    }

    public function getStaffByRole(?string $role, int $perPage, string $sort, string $order, User $actor): array
    {
        $allowed = [Role::OperationsManager->value, Role::Supervisor->value, Role::Technician->value];

        $sort  = in_array($sort, ['name', 'created_at'], true) ? $sort : 'name';
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'asc';

        // Roles each actor may see a full list of, beyond their own record.
        $subordinates = match ($actor->role) {
            Role::Admin, Role::OperationsManager => $allowed,
            Role::Supervisor => [Role::Technician->value],
            default => [],
        };

        if (in_array($actor->role, [Role::Admin, Role::OperationsManager], true)) {
            // ops_admin and ops_manager see everyone: a specific role if requested, otherwise all staff combined.
            if ($role !== null) {
                if (! in_array($role, $subordinates, true)) {
                    throw new BusinessRuleException('Invalid role. Allowed values: ops_manager, supervisor, technician.');
                }
                $roles = [$role];
            } else {
                $roles = $subordinates;
            }
            $userId = null;
        } elseif ($role === null || $role === $actor->role->value) {
            // No filter, or filtering on their own role: staff only ever see their own record.
            $roles  = null;
            $userId = $actor->id;
        } elseif (in_array($role, $subordinates, true)) {
            // Filtering on a subordinate role: full list of that role.
            $roles  = [$role];
            $userId = null;
        } else {
            throw new BusinessRuleException('Invalid role.');
        }

        $paginator = $this->userRepository->getByRoleWithStats($roles, $perPage, $sort, $order, $userId);
        $summary   = $this->userRepository->getSummaryByRole($roles, $userId);

        // Fetch active enquiry numbers for all users on this page in one query
        $userIds = collect($paginator->items())->pluck('id')->all();

        $enquiryMap = [];
        if (! empty($userIds)) {
            $rows = ProjectAssignment::query()
                ->select('project_assignments.user_id', 'enquiries.enquiry_number')
                ->join('enquiries', 'enquiries.id', '=', 'project_assignments.enquiry_id')
                ->whereIn('project_assignments.user_id', $userIds)
                ->whereNotIn('enquiries.status', ['handovered', 'cancelled'])
                ->whereNotNull('enquiries.enquiry_number')
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                $enquiryMap[$row->user_id][] = $row->enquiry_number;
            }
        }

        foreach ($paginator->items() as $user) {
            $user->enquiry_numbers = $enquiryMap[$user->id] ?? [];
        }

        return compact('paginator', 'summary');
    }
}
