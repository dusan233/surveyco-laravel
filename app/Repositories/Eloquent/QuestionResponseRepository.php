<?php

namespace App\Repositories\Eloquent;

use App\Models\QuestionResponse;
use App\Repositories\Interfaces\QuestionResponseRepositoryInterface;


class QuestionResponseRepository extends BaseRepository implements QuestionResponseRepositoryInterface
{
    protected function getModel(): string
    {
        return QuestionResponse::class;
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
