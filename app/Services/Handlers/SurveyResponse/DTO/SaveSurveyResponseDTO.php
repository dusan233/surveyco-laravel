<?php


namespace App\Services\Handlers\SurveyPage\DTO;
use Illuminate\Support\Collection;

class SaveSurveyResponseDTO
{
    public function __construct(
        public readonly string $pageId,
        public readonly string $responseId,
        public readonly string $startTime,
        public readonly Collection $answers,
    ) {
    }
}
