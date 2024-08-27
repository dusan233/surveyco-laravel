<?php


namespace App\Services\Handlers\SurveyPage\DTO;
class SaveQuestionAnswerDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $questionId,
        public readonly string $type,
    ) {
    }
}
