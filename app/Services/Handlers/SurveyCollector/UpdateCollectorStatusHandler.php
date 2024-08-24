<?php

namespace App\Services\Handlers\SurveyCollector;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Services\Handlers\SurveyCollector\DTO\UpdateCollectorStatusDTO;
use Illuminate\Database\DatabaseManager;


class UpdateCollectorStatusHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly SurveyCollectorRepositoryInterface $surveyColelctorRepository,
    ) {
    }

    public function handle(UpdateCollectorStatusDTO $updateCollectorStatusDTO): SurveyCollector
    {
        return $this->databaseManager->transaction(function () use ($updateCollectorStatusDTO) {
            return $this->updateCollectorStatus($updateCollectorStatusDTO);
        });
    }

    private function updateCollectorStatus(UpdateCollectorStatusDTO $updateCollectorStatusDTO)
    {
        return $this->surveyColelctorRepository->updateById($updateCollectorStatusDTO->collector_id, [
            "status" => $updateCollectorStatusDTO->status
        ]);
    }
}
