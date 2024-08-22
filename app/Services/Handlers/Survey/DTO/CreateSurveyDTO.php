<?php


namespace App\Services\Handlers\Survey\DTO;

class CreateSurveyDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $category,
        public readonly string $author_id
    ) {
    }
}
