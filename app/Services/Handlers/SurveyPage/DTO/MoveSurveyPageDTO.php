<?php


namespace App\Services\Handlers\SurveyPage\DTO;

class MoveSurveyPageDTO
{
    public function __construct(
        public readonly string $survey_id,
        public readonly string $source_page_id,
        public readonly string $target_page_id,
        public readonly string $position
    ) {
    }
}
