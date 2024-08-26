<?php

namespace App\Repositories\Interfaces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;


interface QuestionRepositoryInterface extends RepositoryInterface
{
    public function findByPageId(string $pageId);
    public function findWithAnswers(string $pageId, string $responseId): Collection;
    public function resultSummariesByPageId(string $pageId): Collection;
    public function countByPageId(string $pageId): int;
    public function countBySurveyId(string $surveyId): int;
    public function findLastByPageId(string $pageId): Model|null;
}
