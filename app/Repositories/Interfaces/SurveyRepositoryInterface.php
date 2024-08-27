<?php

namespace App\Repositories\Interfaces;
use Carbon\Carbon;
use Illuminate\Support\Collection;


interface SurveyRepositoryInterface extends RepositoryInterface
{
    public function findByCreatorId(string $authorId, string|null $sort);
    public function responseVolumeById(string $surveyId, Carbon $startDate, Carbon $today): Collection;
}
