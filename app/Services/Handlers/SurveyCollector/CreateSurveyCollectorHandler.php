<?php

namespace App\Services\Handlers\SurveyCollector;
use App\Enums\CollectorStatusEnum;
use App\Enums\CollectorTypeEnum;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Services\Handlers\SurveyCollector\DTO\CreateSurveyCollectorDTO;
use Illuminate\Database\DatabaseManager;


class CreateSurveyCollectorHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly SurveyCollectorRepositoryInterface $surveyColelctorRepository,
    ) {
    }

    public function handle(CreateSurveyCollectorDTO $createSurveyCollectorDTO): SurveyCollector
    {
        return $this->databaseManager->transaction(function () use ($createSurveyCollectorDTO) {
            $newCollector = $this->createCollector($createSurveyCollectorDTO);

            return $newCollector;
        });
    }

    private function createCollector(CreateSurveyCollectorDTO $createSurveyCollectorDTO)
    {
        $newCollectorName = $this->generateNewCollectorName($createSurveyCollectorDTO);

        return $this->surveyColelctorRepository->create([
            "type" => $createSurveyCollectorDTO->type,
            "survey_id" => $createSurveyCollectorDTO->survey_id,
            "status" => CollectorStatusEnum::OPEN->value,
            "name" => $newCollectorName
        ]);
    }
    private function generateNewCollectorName(CreateSurveyCollectorDTO $createSurveyCollectorDTO)
    {
        $newCollectorName = $createSurveyCollectorDTO->type === CollectorTypeEnum::WEB_LINK->value
            ? "Web Link " . ($this->surveyColelctorRepository->countBySurveyId($createSurveyCollectorDTO->survey_id, CollectorTypeEnum::WEB_LINK->value)
                + 1)
            : "New Collector";

        return $newCollectorName;
    }

}
