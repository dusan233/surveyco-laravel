<?php


namespace App\Services\Handlers\Question\DTO;
use App\Attributes\CollectionOf;
use Illuminate\Support\Collection;

class CreateQuestionDTO
{
    public function __construct(
        public readonly string $survey_id,
        public readonly string $page_id,
        public readonly string $description,
        public readonly bool $required,
        public readonly string $type,
        public readonly ?bool $randomize,
        public readonly ?string $description_image,
        #[CollectionOf(CreateQuestionChoiceDTO::class)]
        public readonly ?Collection $choices,
    ) {
    }
}
