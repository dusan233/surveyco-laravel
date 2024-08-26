<?php

namespace App\Repositories\Interfaces;
use Illuminate\Database\Eloquent\Model;


interface SurveyPageRepositoryInterface extends RepositoryInterface
{
    public function findBySurveyId(string $surveyId);
    public function findLastBySurveyId(string $surveyId): Model|null;

    public function countBySurveyId(string $surveyId): int;
}
