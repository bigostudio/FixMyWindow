<?php

namespace App\Repositories\Interfaces;

use App\Models\Enquiry;
use Illuminate\Support\Collection;

interface ProjectAssignmentRepositoryInterface
{
    public function findEnquiry(int $id): ?Enquiry;

    /** Delete all current assignments for the enquiry, then bulk-insert new ones. */
    public function replaceAll(int $enquiryId, array $records): void;

    /** Return assignments grouped by section: ['ops_manager' => [...], ...] */
    public function getTeamGrouped(int $enquiryId): array;
}
