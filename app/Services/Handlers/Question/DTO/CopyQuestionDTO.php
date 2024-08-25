<?php


namespace App\Services\Handlers\Question\DTO;

class CopyQuestionDTO
{
    public function __construct(
        public readonly string $survey_id,
        public readonly string $source_question_id,
        public readonly string $target_page_id,
        public readonly ?string $position,
        public readonly ?string $target_question_id,
    ) {
    }
}
