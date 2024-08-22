<?php

namespace App\Services\Handlers\SurveyPage;
use App\Models\SurveyPage;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\SurveyPage\DTO\CreateSurveyPageDTO;
use Illuminate\Database\DatabaseManager;


class CreateSurveyPageHandler
{
    public function __construct(
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle(CreateSurveyPageDTO $createSurveyPageDTO): SurveyPage
    {
        return $this->databaseManager->transaction(function () use ($createSurveyPageDTO) {
            $newPagePosition = $this->calculateNewPagePosition($createSurveyPageDTO->survey_id);

            $page = $this->createPage($createSurveyPageDTO->survey_id, $newPagePosition);

            return $page;
        });
    }
    private function createPage(string $surveyId, int $position)
    {
        return $this->surveyPageRepository->create([
            "display_number" => $position,
            "survey_id" => $surveyId
        ]);
    }
    private function calculateNewPagePosition(string $surveyId): int
    {
        return $this->surveyPageRepository
            ->lockForUpdate()
            ->findLastBySurveyId($surveyId)
            ->display_number + 1;
    }
}
