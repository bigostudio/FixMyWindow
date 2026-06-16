<?php

namespace App\Repositories\Interfaces;

use App\Models\EnquiryNote;
use Illuminate\Database\Eloquent\Collection;

interface EnquiryNoteRepositoryInterface
{
    public function create(array $data): EnquiryNote;

    public function findByEnquiryId(int $enquiryId): Collection;
}
