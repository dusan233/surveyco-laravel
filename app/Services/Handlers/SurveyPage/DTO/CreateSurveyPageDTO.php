<?php


namespace App\Services\Handlers\SurveyPage\DTO;

class CreateSurveyPageDTO
{
    public function __construct(
        public readonly string $survey_id
    ) {
    }
}
