<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Enquiry;
use App\Models\User;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\SurveyOutcome;
use App\Support\Enums\TimelineActorType;
use App\Support\Enums\TimelineStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SurveyService
{
    public function __construct(
        private readonly SurveyRepositoryInterface          $surveyRepo,
        private readonly EnquiryRepositoryInterface         $enquiryRepo,
        private readonly ProjectTimelineRepositoryInterface $timelineRepo,
    ) {}

    public function create(array $data, User $actor): \App\Models\Survey
    {
        $enquiry = $this->enquiryRepo->findById($data['enquiry_id']);
        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($this->surveyRepo->findByEnquiryId($data['enquiry_id'])) {
            throw new BusinessRuleException('A survey has already been initiated for this enquiry.');
        }

        return DB::transaction(function () use ($enquiry, $data, $actor) {
            $survey = $this->surveyRepo->create([
                'enquiry_id'  => $enquiry->id,
                'surveyor_id' => $data['surveyor_id'] ?? null,
            ]);

            $enquiry->update(['status' => ProjectStatus::SurveyInitiated->value]);

            $this->timelineRepo->log([
                'enquiry_id'  => $enquiry->id,
                'status'      => TimelineStatus::InspectionScheduled->value,
                'description' => 'Survey initiated for expert inspection.',
                'actor_type'  => $actor->role->value,
                'actor_id'    => $actor->id,
                'actor_name'  => $actor->name,
            ]);

            return $survey->load(['enquiry', 'surveyor']);
        });
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
            throw new BusinessRuleException('Survey already approved.');
        }

        $outcomeEnum = SurveyOutcome::from($outcome);

        DB::transaction(function () use ($survey, $outcomeEnum, $actor) {
            $survey->update(['outcome' => $outcomeEnum]);

            $enquiry = $this->enquiryRepo->findById($survey->enquiry_id);

            if ($outcomeEnum === SurveyOutcome::Go) {
                $enquiry->update(['status' => ProjectStatus::SurveyCompleted->value]);

                $this->timelineRepo->log([
                    'enquiry_id'  => $survey->enquiry_id,
                    'status'      => TimelineStatus::SurveyPassed->value,
                    'description' => 'Survey completed and approved. Project is ready to proceed.',
                    'actor_type'  => $actor->role->value,
                    'actor_id'    => $actor->id,
                    'actor_name'  => $actor->name,
                ]);

                $this->timelineRepo->log([
                    'enquiry_id'  => $survey->enquiry_id,
                    'status'      => TimelineStatus::WorkOrderCreated->value,
                    'description' => 'Work order created automatically on survey approval.',
                    'actor_type'  => TimelineActorType::System->value,
                    'actor_id'    => null,
                    'actor_name'  => 'System',
                ]);

            } elseif ($outcomeEnum === SurveyOutcome::NoGo) {
                $enquiry->update(['status' => ProjectStatus::Cancelled->value]);

                $this->timelineRepo->log([
                    'enquiry_id'  => $survey->enquiry_id,
                    'status'      => TimelineStatus::SurveyRejected->value,
                    'description' => 'Survey failed. Enquiry cancelled at Go/No-Go stage.',
                    'actor_type'  => $actor->role->value,
                    'actor_id'    => $actor->id,
                    'actor_name'  => $actor->name,
                ]);
            }
            // HOLD: save outcome only, no status change
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
