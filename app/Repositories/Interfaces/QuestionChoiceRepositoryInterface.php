<?php

namespace App\Repositories\Interfaces;


interface QuestionChoiceRepositoryInterface extends RepositoryInterface
{
    public function countByQuestionId(string $questionId): int;
}
