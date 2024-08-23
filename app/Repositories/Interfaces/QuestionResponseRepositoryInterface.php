<?php

namespace App\Repositories\Interfaces;


interface QuestionResponseRepositoryInterface extends RepositoryInterface
{
    public function countByQuestionId(string $questionId): int;
}
