<?php

namespace App\Repositories\Eloquent;

use App\Models\Question;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use Illuminate\Support\Collection;


class QuestionRepository extends BaseRepository implements QuestionRepositoryInterface
{
    protected function getModel(): string
    {
        return Question::class;
    }

    public function findByPageId(string $pageId)
    {
        $this->model = $this->model->orderBy("display_number", "asc");
        return $this->findWhere([
            "survey_page_id" => $pageId
        ]);
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
