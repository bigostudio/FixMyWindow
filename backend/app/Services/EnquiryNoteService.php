<?php

namespace App\Services;

use App\Models\EnquiryNote;
use App\Models\User;
use App\Repositories\Interfaces\EnquiryNoteRepositoryInterface;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnquiryNoteService
{
    public function __construct(
        private readonly EnquiryNoteRepositoryInterface $noteRepo,
        private readonly EnquiryRepositoryInterface     $enquiryRepo,
    ) {}

    public function create(array $data, ?User $actor): EnquiryNote
    {
        if (! $this->enquiryRepo->findById($data['enquiry_id'])) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        return $this->noteRepo->create([
            'enquiry_id'      => $data['enquiry_id'],
            'content'         => $data['content'],
            'created_by'      => $actor?->id,
            'created_by_name' => $actor?->name,
        ]);
    }

    public function listByEnquiry(int $enquiryId): Collection
    {
        if (! $this->enquiryRepo->findById($enquiryId)) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        return $this->noteRepo->findByEnquiryId($enquiryId);
    }
}
