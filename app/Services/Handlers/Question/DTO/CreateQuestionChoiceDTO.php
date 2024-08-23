<?php


namespace App\Services\Handlers\Question\DTO;

class CreateQuestionChoiceDTO
{
    public function __construct(
        public readonly string $description,
        public readonly string $position,
        public readonly ?string $description_image,
    ) {
    }
}
