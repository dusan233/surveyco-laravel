<?php

namespace App\Repositories\Eloquent;

use App\Models\SurveyPage;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use Illuminate\Database\Eloquent\Model;


class SurveyPageRepository extends BaseRepository implements SurveyPageRepositoryInterface
{
    protected function getModel(): string
    {
        return SurveyPage::class;
    }

    public function countBySurveyId(string $surveyId): int
    {
        $this->applyConditions([
            "survey_id",
            $surveyId
        ]);

        $count = $this->model->count();
        $this->resetModel();

        return $count;
    }

    public function findLastBySurveyId(string $surveyId): Model|null
    {
        $this->model = $this->model->orderByDesc("display_number");
        return $this->findFirstWhere([
            "survey_id" => $surveyId
        ]);
    }
    public function findBySurveyId(string $surveyId)
    {
        $this->model = $this->model->orderBy("display_number", "asc");
        return $this->findWhere([
            "survey_id" => $surveyId
        ]);
    }
}
