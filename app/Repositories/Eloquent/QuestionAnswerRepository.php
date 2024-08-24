<?php

namespace App\Repositories\Eloquent;


use App\Models\QuestionResponseAnswer;
use App\Repositories\Interfaces\QuestionAnswerRepositoryInterface;


class QuestionAnswerRepository extends BaseRepository implements QuestionAnswerRepositoryInterface
{
    protected function getModel(): string
    {
        return QuestionResponseAnswer::class;
    }
}
