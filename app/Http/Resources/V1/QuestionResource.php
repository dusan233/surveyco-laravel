<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "description" => $this->description,
            "descriptionImage" => $this->description_image,
            "type" => $this->type,
            "displayNumber" => $this->display_number,
            "required" => $this->required,
            "randomize" => $this->whenNotNull($this->randomize),
        ];

        if ($this->relationLoaded("choices") && $this->choices->isNotEmpty()) {
            $array["choices"] = QuestionChoiceResource::collection($this->choices);
        }

        if ($this->relationLoaded("questionResponses") && $this->questionResponses->isNotEmpty()) {
            if (isset($this->questionResponses[0]->questionResponseAnswers)) {
                $array["answers"] = QuestionResponseAnswerResource::collection($this->questionResponses[0]->questionResponseAnswers);
            }
        }

        return $array;
    }
}
