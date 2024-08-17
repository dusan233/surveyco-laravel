<?php

namespace App\Repositories\Eloquent;

use App\Models\Survey;
use App\Repositories\Interfaces\SurveyRepositoryInterface;


class SurveyRepository extends BaseRepository implements SurveyRepositoryInterface
{
    protected function getModel(): string
    {
        return Survey::class;
    }
}
