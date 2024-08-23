<?php

namespace App\Repositories\Eloquent;


use App\Models\QuestionChoice;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;


class QuestionChoiceRepository extends BaseRepository implements QuestionChoiceRepositoryInterface
{
    protected function getModel(): string
    {
        return QuestionChoice::class;
    }

    public function countByQuestionId(string $questionId): int
    {
        $this->applyConditions([
            "question_id" => $questionId
        ]);

        $count = $this->model->count();
        $this->resetModel();

        return $count;
    }
}
