<?php

namespace App\Repositories\Interfaces;

use App\Models\Survey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SurveyRepositoryInterface
{
    public function create(array $data): Survey;

    public function findById(int $id): ?Survey;

    public function updateChecklist(Survey $survey, array $data): Survey;

    public function updateOutcome(Survey $survey, string $outcome): Survey;

    public function findByEnquiryId(int $enquiryId): ?Survey;

    public function paginate(int $page, int $limit, ?int $enquiryId = null): LengthAwarePaginator;
}
