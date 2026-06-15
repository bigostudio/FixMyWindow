<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function getByRoles(array $roles): Collection
    {
        return User::whereIn('role', $roles)->where('is_active', true)->get();
    }

    public function getPending(int $page, int $limit): LengthAwarePaginator
    {
        return User::where('is_active', false)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function getByRoleWithStats(string $role, int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        return User::where('role', $role)
            ->where('is_active', true)
            ->addSelect([
                'active_projects' => DB::table('project_assignments')
                    ->selectRaw('COUNT(DISTINCT project_assignments.enquiry_id)')
                    ->join('enquiries', 'enquiries.id', '=', 'project_assignments.enquiry_id')
                    ->whereColumn('project_assignments.user_id', 'users.id')
                    ->whereNotIn('enquiries.status', ['handovered', 'cancelled'])
                    ->whereNotNull('enquiries.enquiry_number'),
                'completed' => DB::table('project_assignments')
                    ->selectRaw('COUNT(DISTINCT project_assignments.enquiry_id)')
                    ->join('enquiries', 'enquiries.id', '=', 'project_assignments.enquiry_id')
                    ->whereColumn('project_assignments.user_id', 'users.id')
                    ->where('enquiries.status', 'handovered'),
            ])
            ->orderBy($sort, $order)
            ->paginate($perPage);
    }

    public function getSummaryByRole(string $role): array
    {
        $total = User::where('role', $role)->where('is_active', true)->count();

        $stats = DB::table('project_assignments')
            ->join('enquiries', 'enquiries.id', '=', 'project_assignments.enquiry_id')
            ->join('users', 'users.id', '=', 'project_assignments.user_id')
            ->where('users.role', $role)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN enquiries.status NOT IN ('handovered', 'cancelled') AND enquiries.enquiry_number IS NOT NULL THEN enquiries.id END) as total_active_projects,
                COUNT(DISTINCT CASE WHEN enquiries.status = 'handovered' THEN enquiries.id END) as total_completed
            ")
            ->first();

        return [
            'total'                 => $total,
            'total_active_projects' => (int) ($stats->total_active_projects ?? 0),
            'total_completed'       => (int) ($stats->total_completed ?? 0),
        ];
    }
}
