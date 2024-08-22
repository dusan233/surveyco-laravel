<?php

namespace App\Repositories\Interfaces;


interface SurveyCollectorRepositoryInterface extends RepositoryInterface
{
    public function findBySurveyId(string $surveyId, string|null $sort);
    public function countBySurveyId(string $surveyId, ?string $type): int;
}
