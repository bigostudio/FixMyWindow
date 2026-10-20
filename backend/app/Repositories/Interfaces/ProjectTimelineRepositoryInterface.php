<?php

namespace App\Repositories\Interfaces;

use App\Models\ProjectTimeline;

interface ProjectTimelineRepositoryInterface
{
    public function log(array $data): ProjectTimeline;
}
