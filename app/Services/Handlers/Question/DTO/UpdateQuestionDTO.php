<?php


namespace App\Services\Handlers\Question\DTO;
use App\Attributes\CollectionOf;
use Illuminate\Support\Collection;

class UpdateQuestionDTO
{
    public function __construct(
        public readonly string $question_id,
        public readonly string $description,
        public readonly bool $required,
        public readonly ?bool $randomize,
        public readonly ?string $description_image,
        #[CollectionOf(UpdateQuestionChoiceDTO::class)]
        public readonly ?Collection $choices,
    ) {
    }
}
