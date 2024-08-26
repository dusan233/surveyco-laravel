<?php

namespace App\Repositories\Eloquent;

use App\Models\Question;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;


class QuestionRepository extends BaseRepository implements QuestionRepositoryInterface
{
    protected function getModel(): string
    {
        return Question::class;
    }

    public function findByPageId(string $pageId): Collection
    {
        $this->model = $this->model->orderBy("display_number", "asc");
        return $this->findWhere([
            "survey_page_id" => $pageId
        ]);
    }

    public function findLastByPageId(string $pageId): Model|null
    {
        $this->model = $this->model->orderByDesc("display_number");

        return $this->findFirstWhere([
            "survey_page_id" => $pageId
        ]);
    }

    public function countByPageId(string $pageId): int
    {
        $this->model = $this->model->where("survey_page_id", $pageId);

        $count = $this->model->count();
        $this->resetModel();

        return $count;
    }

    public function countBySurveyId(string $surveyId): int
    {
        $this->model = $this->model->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
            $query->where("survey_id", $surveyId);
        });

        $count = $this->model->count();
        $this->resetModel();

        return $count;
    }

    public function findWithAnswers(string $pageId, string $responseId): Collection
    {
        $this->model = $this->model->with([
            "questionResponses" => function ($query) use ($responseId) {
                $query->where("survey_response_id", $responseId)
                    ->with("questionResponseAnswers");
            },
            "choices"
        ]);

        return $this->findWhere([
            "survey_page_id" => $pageId
        ]);
    }

    public function resultSummariesByPageId(string $pageId): Collection
    {
        return $this->model
            ->where("survey_page_id", $pageId)
            ->withCount("questionResponses")
            ->with([
                "choices" => function ($query) {
                    $query->withCount("questionResponseAnswers");
                },
            ])
            ->get();
    }
}
