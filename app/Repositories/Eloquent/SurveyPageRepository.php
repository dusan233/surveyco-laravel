<?php

namespace App\Repositories\Eloquent;

use App\Models\SurveyPage;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;


class SurveyPageRepository extends BaseRepository implements SurveyPageRepositoryInterface
{
    protected function getModel(): string
    {
        return SurveyPage::class;
    }

    public function findBySurveyId(string $surveyId)
    {
        $this->model = $this->model->orderBy("display_number", "asc");
        return $this->findWhere([
            "survey_id" => $surveyId
        ]);
    }
}
