<?php

namespace App\Repositories\Interfaces;


interface SurveyPageRepositoryInterface extends RepositoryInterface
{
    public function findBySurveyId(string $surveyId);
}
