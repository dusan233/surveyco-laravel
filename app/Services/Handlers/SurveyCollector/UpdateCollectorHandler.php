<?php

namespace App\Services\Handlers\SurveyCollector;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Services\Handlers\SurveyCollector\DTO\UpdateCollectorDTO;
use Illuminate\Database\DatabaseManager;


class UpdateCollectorHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly SurveyCollectorRepositoryInterface $surveyColelctorRepository,
    ) {
    }

    public function handle(UpdateCollectorDTO $updateCollectorDTO): SurveyCollector
    {
        return $this->databaseManager->transaction(function () use ($updateCollectorDTO) {
            return $this->updateCollector($updateCollectorDTO);
        });
    }

    private function updateCollector(UpdateCollectorDTO $updateCollectorDTO)
    {
        return $this->surveyColelctorRepository->updateById($updateCollectorDTO->collector_id, [
            "name" => $updateCollectorDTO->name
        ]);
    }
}
