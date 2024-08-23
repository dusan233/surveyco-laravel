<?php


namespace App\Services\Handlers\Question\DTO;

class UpdateQuestionChoiceDTO
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $description,
        public readonly int $position,
        public readonly ?string $description_image,
    ) {
    }
}
