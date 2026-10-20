<?php

namespace App\Repositories\Eloquent;

use App\Models\EnquiryNote;
use App\Repositories\Interfaces\EnquiryNoteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentEnquiryNoteRepository implements EnquiryNoteRepositoryInterface
{
    public function create(array $data): EnquiryNote
    {
        return EnquiryNote::create($data);
    }

    public function findByEnquiryId(int $enquiryId): Collection
    {
        return EnquiryNote::where('enquiry_id', $enquiryId)
            ->orderByDesc('created_at')
            ->get();
    }
}
