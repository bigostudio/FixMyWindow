<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\SurveyOutcome;
use App\Support\Enums\TimelineStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SurveyService
{
    public function __construct(
        private readonly SurveyRepositoryInterface $surveyRepo,
        private readonly EnquiryRepositoryInterface $enquiryRepo,
        private readonly ProjectTimelineRepositoryInterface $timelineRepo,
    ) {}

    public function create(array $data): \App\Models\Survey
    {
        $enquiry = $this->enquiryRepo->findById($data['enquiry_id']);
        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        return $this->surveyRepo->create([
            'enquiry_id'  => $data['enquiry_id'],
            'surveyor_id' => $data['surveyor_id'] ?? null,
        ]);
    }

    public function updateChecklist(int $surveyId, array $data): \App\Models\Survey
    {
        $survey = $this->surveyRepo->findById($surveyId);
        if (! $survey) {
            throw new NotFoundHttpException('Survey not found.');
        }

        if ($survey->outcome === SurveyOutcome::Go) {
            throw new BusinessRuleException('Survey already approved. Checklist cannot be modified.');
        }

        return $this->surveyRepo->updateChecklist($survey, $data);
    }

    public function submitGoNoGo(int $surveyId, string $outcome, User $actor): \App\Models\Survey
    {
        $survey = $this->surveyRepo->findById($surveyId);
        if (! $survey) {
            throw new NotFoundHttpException('Survey not found.');
        }

        if ($survey->outcome === SurveyOutcome::Go) {
            throw new BusinessRuleException('Survey already approved. A project has been created.');
        }

        $outcomeEnum = SurveyOutcome::from($outcome);

        DB::transaction(function () use ($survey, $outcomeEnum, $actor) {
            $survey->update(['outcome' => $outcomeEnum]);

            if ($outcomeEnum === SurveyOutcome::Go) {
                $project = Project::create([
                    'enquiry_id' => $survey->enquiry_id,
                    'status'     => ProjectStatus::OnTrack,
                ]);

                $this->timelineRepo->log([
                    'project_id'  => $project->id,
                    'enquiry_id'  => $survey->enquiry_id,
                    'status'      => TimelineStatus::SurveyPassed->value,
                    'description' => 'Survey completed and approved. Project is ready to proceed.',
                    'actor_type'  => $actor->role->value,
                    'actor_id'    => $actor->id,
                    'actor_name'  => $actor->name,
                ]);

                $this->timelineRepo->log([
                    'project_id'  => $project->id,
                    'enquiry_id'  => $survey->enquiry_id,
                    'status'      => TimelineStatus::WorkOrderCreated->value,
                    'description' => 'Work order created automatically on survey approval.',
                    'actor_type'  => 'system',
                    'actor_id'    => null,
                    'actor_name'  => 'System',
                ]);

            } elseif ($outcomeEnum === SurveyOutcome::NoGo) {
                $this->timelineRepo->log([
                    'project_id'  => null,
                    'enquiry_id'  => $survey->enquiry_id,
                    'status'      => TimelineStatus::SurveyRejected->value,
                    'description' => 'Survey failed. Project declined at Go/No-Go stage.',
                    'actor_type'  => $actor->role->value,
                    'actor_id'    => $actor->id,
                    'actor_name'  => $actor->name,
                ]);
            }
            // HOLD: save decision only, no project, no timeline entry
        });

        return $survey->refresh();
    }

    public function list(int $page, int $limit, ?int $enquiryId = null): LengthAwarePaginator
    {
        return $this->surveyRepo->paginate($page, $limit, $enquiryId);
    }

    public function show(int $id): \App\Models\Survey
    {
        $survey = $this->surveyRepo->findById($id);
        if (! $survey) {
            throw new NotFoundHttpException('Survey not found.');
        }

        return $survey;
    }
}
