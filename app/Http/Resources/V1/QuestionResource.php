<?php

namespace App\Http\Resources\V1;

use App\Models\QuestionChoice;
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
            "choices" => QuestionChoiceResource::collection($this->whenLoaded("choices")),
        ];

        return $array;
    }
}
