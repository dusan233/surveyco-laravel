<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "title" => $this->title,
            "category" => $this->category,
            "author" => new UserResource($this->whenLoaded("author")),
            "totalQuestions" => $this->whenCounted("questions", $this->questions_count),
            "totalResponses" => $this->whenCounted("responses", $this->responses_count),
            "totalSurveyPages" => $this->whenCounted("pages", $this->pages_count),
        ];
    }
}
