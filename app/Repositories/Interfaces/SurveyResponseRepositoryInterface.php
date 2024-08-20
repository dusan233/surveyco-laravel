<?php

namespace App\Repositories\Interfaces;


interface SurveyResponseRepositoryInterface extends RepositoryInterface
{
    public function findBySurveyId(string $surveyId, string|null $sort);

    public function countBySurveyId(string $surveyId): int;
}
