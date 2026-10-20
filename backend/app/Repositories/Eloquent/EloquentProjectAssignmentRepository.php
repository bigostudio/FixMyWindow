<?php

namespace App\Repositories\Eloquent;

use App\Models\Enquiry;
use App\Models\ProjectAssignment;
use App\Repositories\Interfaces\ProjectAssignmentRepositoryInterface;
use App\Support\Enums\AssignmentSection;
use Illuminate\Support\Facades\DB;

class EloquentProjectAssignmentRepository implements ProjectAssignmentRepositoryInterface
{
    public function findEnquiry(int $id): ?Enquiry
    {
        return Enquiry::find($id);
    }

    public function replaceAll(int $enquiryId, array $records): void
    {
        DB::transaction(function () use ($enquiryId, $records) {
            ProjectAssignment::where('enquiry_id', $enquiryId)->delete();

            if (! empty($records)) {
                ProjectAssignment::insert($records);
            }
        });
    }

    public function getTeamGrouped(int $enquiryId): array
    {
        $rows = ProjectAssignment::with('user')
            ->where('enquiry_id', $enquiryId)
            ->orderBy('created_at')
            ->get();

        $grouped = [];
        foreach (AssignmentSection::cases() as $section) {
            $grouped[$section->value] = [];
        }

        foreach ($rows as $row) {
            $key = $row->assignment_section instanceof AssignmentSection
                ? $row->assignment_section->value
                : $row->assignment_section;

            if (array_key_exists($key, $grouped)) {
                $grouped[$key][] = [
                    'id'    => $row->user->id,
                    'name'  => $row->user->name,
                    'phone' => $row->user->phone,
                    'role'  => $row->role,
                ];
            }
        }

        return $grouped;
    }
}
