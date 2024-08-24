<?php

namespace App\Services\Handlers\SurveyCollector;
use App\Repositories\Interfaces\QuestionAnswerRepositoryInterface;
use App\Repositories\Interfaces\QuestionResponseRepositoryInterface;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;


class DeleteCollectorHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly SurveyCollectorRepositoryInterface $surveyColelctorRepository,
        private readonly SurveyResponseRepositoryInterface $surveyResponseRepository,
        private readonly QuestionResponseRepositoryInterface $questionResponseRepository,
        private readonly QuestionAnswerRepositoryInterface $questionAnswerRepository,
    ) {
    }

    public function handle(string $collectorId): void
    {
        $this->databaseManager->transaction(function () use ($collectorId) {
            $this->deleteCollector($collectorId);
        });
    }

    private function deleteCollector(string $collectorId)
    {
        $this->questionAnswerRepository->deleteWhere([
            function (Builder $query) use ($collectorId) {
                $query->whereHas("questionResponse", function (Builder $query) use ($collectorId) {
                    $query->whereHas("surveyResponse", function (Builder $query) use ($collectorId) {
                        $query->where("survey_collector_id", $collectorId);
                    });
                });
            }
        ]);

        $this->questionResponseRepository->deleteWhere([
            function (Builder $query) use ($collectorId) {
                $query->whereHas("surveyResponse", function (Builder $query) use ($collectorId) {
                    $query->where("survey_collector_id", $collectorId);
                });
            }
        ]);

        $this->surveyResponseRepository->deleteWhere([
            "survey_collector_id" => $collectorId
        ]);

        $this->surveyColelctorRepository->deleteById($collectorId);
    }
}
