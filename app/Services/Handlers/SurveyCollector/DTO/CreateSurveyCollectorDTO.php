<?php


namespace App\Services\Handlers\SurveyCollector\DTO;

class CreateSurveyCollectorDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $survey_id
    ) {
    }
}
