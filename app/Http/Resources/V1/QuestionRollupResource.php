<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionRollupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $array = [
            "id" => $this->id,
            "type" => $this->type,
            "answered" => $this->question_responses_count,
            "skipped" => $this->survey_responses_count - $this->question_responses_count,
        ];

        if ($this->relationLoaded("choices") && $this->choices->isNotEmpty()) {
            $array["choices"] = QuestionChoiceRollupResource::collection($this->choices);
        }

        return $array;
    }
}
