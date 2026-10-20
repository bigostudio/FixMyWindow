<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectTimeline;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;

class EloquentProjectTimelineRepository implements ProjectTimelineRepositoryInterface
{
    public function log(array $data): ProjectTimeline
    {
        return ProjectTimeline::create($data);
    }
}
