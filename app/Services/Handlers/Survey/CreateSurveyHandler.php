<?php

namespace App\Services\Handlers\Survey;
use App\Models\Survey;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Services\Handlers\Survey\DTO\CreateSurveyDTO;
use Illuminate\Database\DatabaseManager;



class CreateSurveyHandler
{
    public function __construct(
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly SurveyRepositoryInterface $surveyRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle(CreateSurveyDTO $createSurveyDTO): Survey
    {
        return $this->databaseManager->transaction(function () use ($createSurveyDTO) {
            $survey = $this->createSurvey($createSurveyDTO);

            $this->createInitialSurveyPage($survey->id);

            return $survey;
        });
    }


    private function createSurvey(CreateSurveyDTO $createSurveyDTO)
    {
        return $this->surveyRepository->create([
            "title" => $createSurveyDTO->title,
            "category" => $createSurveyDTO->category,
            "author_id" => $createSurveyDTO->author_id
        ]);
    }

    private function createInitialSurveyPage(string $surveyId)
    {
        return $this->surveyPageRepository->create([
            "display_number" => 1,
            "survey_id" => $surveyId
        ]);
    }
}
