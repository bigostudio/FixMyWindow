<?php

namespace App\Repositories\Eloquent;

use App\Models\Survey;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSurveyRepository implements SurveyRepositoryInterface
{
    public function create(array $data): Survey
    {
        return Survey::create($data);
    }

    public function findById(int $id): ?Survey
    {
        return Survey::with(['enquiry', 'surveyor'])->find($id);
    }

    public function updateChecklist(Survey $survey, array $data): Survey
    {
        $survey->update($data);
        return $survey->refresh();
    }

    public function updateOutcome(Survey $survey, string $outcome): Survey
    {
        $survey->update(['outcome' => $outcome]);
        return $survey->refresh();
    }

    public function paginate(int $page, int $limit, ?int $enquiryId = null): LengthAwarePaginator
    {
        return Survey::with(['enquiry', 'surveyor'])
            ->when($enquiryId, fn($q) => $q->where('enquiry_id', $enquiryId))
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }
}
