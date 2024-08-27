<?php

namespace App\Services\Handlers\SurveyPage;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Illuminate\Database\DatabaseManager;


class SaveSurveyResponseHandler
{
    public function __construct(
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly SurveyResponseRepositoryInterface $surveyResponseRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle()
    {
        return $this->databaseManager->transaction(function () {

        });
    }

}
