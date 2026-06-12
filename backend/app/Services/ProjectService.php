<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectService
{
    public function list(int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        $allowedSorts = ['created_at', 'status'];
        $sort  = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        return Project::with(['enquiry.customer', 'enquiry.service', 'assignments.user'])
            ->orderBy($sort, $order)
            ->paginate($perPage);
    }

    public function show(int $id): Project
    {
        $project = Project::with([
            'enquiry.customer',
            'enquiry.service',
            'assignments.user',
            'timeline',
        ])->find($id);

        if (! $project) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }
}
