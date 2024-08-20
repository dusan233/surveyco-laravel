<?php

namespace App\Repositories\Interfaces;
use Illuminate\Support\Collection;


interface QuestionRepositoryInterface extends RepositoryInterface
{
    public function findByPageId(string $pageId);
    public function findWithAnswers(string $pageId, string $responseId): Collection;
    public function resultSummariesByPageId(string $pageId): Collection;
}
